<?php
//
// Description
// ===========
// This function will check the user has access to the projects module,
// and return a list of other modules enabled for the business.
//
// Arguments
// =========
// ciniki:
// business_id: 		The ID of the business the request is for.
// method:				The method requested.
// project_id:			The ID of the project to check the access for.  If specified as 0 then
//						it is not checked.
// 
// Returns
// =======
//
function ciniki_projects_checkAccess($ciniki, $business_id, $method, $project_id) {
	//
	// Check if the business is active and the module is enabled
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
	$rc = ciniki_businesses_checkModuleAccess($ciniki, $business_id, 'ciniki', 'projects');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['modules']) ) {
		$modules = $rc['modules'];
	} else {
		$modules = array();
	}

	if( !isset($rc['ruleset']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'820', 'msg'=>'No permissions granted'));
	}

	//
	// Sysadmins are allowed full access, except for deleting.
	//
	if( $method != 'ciniki.projects.delete' ) {
		if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
			return array('stat'=>'ok', 'modules'=>$modules);
		}
	}

	//
	// Users who are an owner or employee of a business can see the business project
	//
	$strsql = "SELECT business_id, user_id FROM ciniki_business_users "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
		. "AND package = 'ciniki' "
		. "AND (permission_group = 'owners' OR permission_group = 'employees') "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// If the project_id is specified, check if the user has permission to view it
	//
	if( $project_id > 0 ) {
		$strsql = "SELECT id "
			. "FROM ciniki_projects "
			. "WHERE project_id = '" . ciniki_core_dbQuote($ciniki, $project_id) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$prc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.projects', 'project');
		if( $prc['stat'] != 'ok' ) {
			return $prc;
		}
		if( !isset($prc['project']['id']) || $prc['project']['id'] != $project_id ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'832', 'msg'=>'Invalid project'));
		}
	}

	//
	// If the user has permission, return ok
	//
	if( isset($rc['rows']) && isset($rc['rows'][0]) 
		&& $rc['rows'][0]['user_id'] > 0 && $rc['rows'][0]['user_id'] == $ciniki['session']['user']['id'] ) {
		return array('stat'=>'ok', 'modules'=>$modules);
	}

	//
	// By default, fail
	//
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'821', 'msg'=>'Access denied.'));
}
?>