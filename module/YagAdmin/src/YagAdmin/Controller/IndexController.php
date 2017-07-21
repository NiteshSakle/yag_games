<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace YagAdmin\Controller;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Aws\S3\S3Client;
use Zend\Db\Sql\Expression;


class IndexController extends BaseController {

    //displaying all contests pagewise(page_size = 10)
    public function indexAction() {
        $this->checkLogin();

        $add_contest = 0;
        $delete_contest = 0;
        $review_contest = 0;

        if ($permissions = $_SESSION['admin_user']['permissions']) {

            foreach ($permissions as $permission) {
                if ($permission == 'add-contest') {
                    $add_contest = 1;
                }
                if ($permission == 'delete-contest') {
                    $delete_contest = 1;
                }
                if ($permission == 'review-contest') {
                    $review_contest = 1;
                }
            }
        }
        $types = array();
        $contests = array();
        $totalPages = 0;

        $page = $this->params()->fromQuery('page', 1);

        $types = $this->getContestTypes();
        $data = $this->getContests($page);

        if ($data['total']) {
            $totalPages = ceil($data['total'] / 10);
        }

        return new ViewModel(array('types' => $types, 'contests' => $data['contests'], 'currentPage' => $page, 'totalPages' => $totalPages, 'add_contest' => $add_contest, 'delete_contest' => $delete_contest, 'review_contest' => $review_contest));
    }

    //displaying particular contest details and all the photos uploaded to that contest
    public function contestDetailsAction() {
        $this->checkLogin();

        $contestDetails = array();
        $contestPhotos = array();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = trim($this->getRequest()->getPost('id'));

            $contestDetails = $this->getContestDetails($id);
            $contestPhotos = $this->getUserContestPhotos($id);
        } else {
            $this->redirect()->toRoute('admin');
        }
        
        $allowToModifyRankings = false;
        
        if ($contestDetails['type_id'] == '1' && $contestDetails['voting_started'] == '1' && $contestDetails['winners_announced'] == '0') {
            $allowToModifyRankings = true;
            $contestRankingsModifyTable = $this->getServiceLocator()->get('YagGames\Model\ContestRankingsModifyTable');
            $contestRankingsModify = $contestRankingsModifyTable->fetchRecordsByContest($contestDetails['id']);
            
            foreach($contestPhotos as $key => $row) {                
                if (array_key_exists($row['id'], $contestRankingsModify)) {                    
                    $contestPhotos[$key]['intended_rank'] = $contestRankingsModify[$row['id']]['intended_rank'];
                }
            }
        }        
        
        return new ViewModel(array('contestDetails' => $contestDetails, 'contestPhotos' => $contestPhotos, 'allowToModifyRankings' => $allowToModifyRankings));
    }

    //returning particular contest details for edit operation
    public function getContestDetailAction() {
        $this->checkLogin();

        $data = array();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = trim($this->getRequest()->getPost('id'));

            $data = $this->getContestDetails($id);
        }

        return new JsonModel(array('data' => $data));
    }

    //adding and editing of contest
    public function saveContestAction() {
        $this->checkLogin();
        $add_contest = 0;
        $delete_contest = 0;
        $review_contest = 0;

        if ($permissions = $_SESSION['admin_user']['permissions']) {

            foreach ($permissions as $permission) {
                if ($permission == 'add-contest') {
                    $add_contest = 1;
                }
                if ($permission == 'delete-contest') {
                    $delete_contest = 1;
                }
                if ($permission == 'review-contest') {
                    $review_contest = 1;
                }
            }
        }

        $response = array();

        //checking for correct permissions
        if ($add_contest) {

            $request = $this->getRequest();
            if ($request->isPost()) {

                $params['id'] = trim($this->getRequest()->getPost('id'));
                $params['name'] = trim($this->getRequest()->getPost('name'));
                $params['description'] = $this->getRequest()->getPost('description');
                $params['entryStartDate'] = $this->getRequest()->getPost('entryStartDate');
                $params['entryEndDate'] = $this->getRequest()->getPost('entryEndDate');
                $params['winnersAnnounceDate'] = $this->getRequest()->getPost('winnersAnnounceDate');
                $params['votingStartDate'] = $this->getRequest()->getPost('votingStartDate');
                $params['entryLimit'] = $this->getRequest()->getPost('entryLimit');
                $params['type'] = $this->getRequest()->getPost('type');
                $params['exclusive'] = $this->getRequest()->getPost('exclusive');
                $params['publishContest'] = $this->getRequest()->getPost('publishContest');
                $thumbnail = $this->getRequest()->getFiles('thumbnail');
                if($params['type'] == '3') {
                    $params['br_round1'] = $this->getRequest()->getPost('br_round1');
                    $params['br_round2'] = $this->getRequest()->getPost('br_round2');
                    $params['br_round3'] = $this->getRequest()->getPost('br_round3');
                    $params['br_round4'] = $this->getRequest()->getPost('br_round4');
                    $params['br_round5'] = $this->getRequest()->getPost('br_round5');
                    $params['br_round6'] = $this->getRequest()->getPost('br_round6');                    
                }

                //checking for all the empty fields here except thumbnail to avoid multiple uploading of thumbnails
                if (empty($params['name']) || empty($params['description']) || empty($params['entryStartDate']) || empty($params['entryEndDate']) || empty($params['winnersAnnounceDate']) || empty($params['votingStartDate']) || empty($params['entryLimit']) || empty($params['type'])) {

                    $response['success'] = false;
                    $response['message'] = 'Please fill in all fields';
                    return new JsonModel($response);
                }
                
                if (!$params['id'] && (!isset($params['exclusive']) || !isset($params['publishContest']))) {
                    $response['success'] = false;
                    $response['message'] = 'Please fill in all fields';
                    return new JsonModel($response);
                }
                //checking for whether a thumbnail is uploaded or not
                if (($thumbnail['error'] == 4 || $thumbnail['size'] == 0) && isset($params['id'])) {
                    $params['thumbnail'] = 'NOT_UPDATED'; //set to NOT_UPDATED while editing a contest, will be used further
                } else {
                    $fileType = $thumbnail['type'];
                    $allowedImageTypes = array("image/pjpeg", "image/jpeg", "image/jpg", "image/png", "image/x-png", "image/gif");
                    //checking for valid image file
                    if ($thumbnail['error'] == 4 || $thumbnail['size'] == 0 || !in_array($thumbnail['type'], $allowedImageTypes)) {
                        $response['success'] = false;
                        $response['message'] = 'Please attach a valid image';

                        return new JsonModel($response);
                    } else {
                        $fileDetails = explode(".", $thumbnail['name']);
                        $fileName = preg_replace('/[^\da-z]/i', '', $fileDetails['0']);
                        
                        $name = strtotime("now") . $fileName . '.' . $fileDetails['1'];
                        $config = $this->getConfig();
                        $destination = $config['upload_path'] . $name;

                        //saving the thumbnail to local server
                        if (move_uploaded_file($thumbnail['tmp_name'], $destination)) {

                            $pathToS3File = "contest/" . $name;

                            $aws_key = $config['aws']['key'];
                            $aws_secret = $config['aws']['secret'];
                            $bucket = $config['aws']['bucket'];
                            $version = $config['aws']['version'];
                            $region = $config['aws']['region'];

                            //creating a s3 client
                            $s3Client = S3Client::factory(array(
                                        'credentials' => array(
                                            'key' => $aws_key,
                                            'secret' => $aws_secret,
                                        ),
                                        'region' => $region,
                                        'version' => $version
                            ));

                            //putting the thumbnail(object) in s3 server in the specified bucket
                            try {
                                $s3Client->putObject([
                                    'Bucket' => $bucket,
                                    'Key' => $pathToS3File,
                                    'Body' => fopen($destination, 'r'),
                                    'ACL' => 'public-read',
                                ]);

                                unlink($destination);
                            } catch (Aws\Exception\S3Exception $e) {
                                $response['success'] = false;
                                $response['message'] = 'There was problem while uploading image to s3';

                                return new JsonModel($response);
                            }

                            $params['thumbnail'] = $name;
                        } else {
                            $response['success'] = false;
                            $response['message'] = 'There was problem while uploading image';

                            return new JsonModel($response);
                        }
                    }
                }

                //checking only for thumbnail here because checked for all other fields before uploading thumbnail
                if (empty($params['thumbnail'])) {

                    $response['success'] = false;
                    $response['message'] = 'Please fill in all fields';
                    return new JsonModel($response);
                } else {

                    $contest = new \YagGames\Model\Contest();

                    $contest->name = $params['name'];
                    $contest->description = $params['description'];
                    $contest->entry_start_date = $this->reformatDate($params['entryStartDate']);
                    $contest->entry_end_date = $this->reformatDate($params['entryEndDate']);
                    $contest->winners_announce_date = $this->reformatDate($params['winnersAnnounceDate']);
                    $contest->voting_start_date = $this->reformatDate($params['votingStartDate']);
                    $contest->max_no_of_photos = $params['entryLimit'];
                    $contest->is_exclusive = $params['exclusive'];
                    $contest->type_id = $params['type'];
                    $contest->publish_contest = $params['publishContest'];

                    $fbscrap = $this->getServiceLocator()->get('fbScrapService');
                    $config = $this->getConfig();  
                    //checking for editing(updating) or creating(insert) the contest
                    if ($params['id']) {
                        $contest->id = $params['id'];
                        if ($params['thumbnail'] != 'NOT_UPDATED') {
                            $contest->thumbnail = $params['thumbnail'];
                        }
                        
                        $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
                        $oldContestInfo = $contestTable->getByContestId($params['id']);
                        
                        $data = $contestTable->update($contest);
                        if($params['type'] == '3' && $data) {
                            $params['contest_id'] = $params['id'];
                            $this->insertOrUpdateBracketRounds($params);
                        }
                                                                    
                        $adminActivityTrackTable = $this->getServiceLocator()->get('YagGames\Model\AdminActivityTrackTable');                        
                        $insertData['admin_id'] = $_SESSION['admin_user']['admin_id'];
                        $insertData['form_name']  = "Manager > Edit Contest";
                        $insertData['comment'] = "Contest ". $params['name']. " [". $params['id']. "] Updated";
                            
                        $insertData['change_type'] = "FIELD UPDATE";
                        
                        $contestType = [
                            '1' => 'Photo Contest', 
                            '2' => 'Fan Favourite', 
                            '3' => 'Brackets',                             
                        ];
                        
                        $radioType = [
                            '0' => "No",
                            "1" => "Yes"
                        ];
                        
                        $insertData['field_name'] = "Name";
                        $insertData['old_value'] = $oldContestInfo['name'];
                        $insertData['new_value'] = $contest->name;                        
                        $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);

                        $insertData['field_name'] = "description";
                        $insertData['old_value'] = $oldContestInfo['description'];
                        $insertData['new_value'] = $contest->description;
                        $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);
                        
                        if($contest->publish_contest != '') {
                            $insertData['field_name'] = "Publish Contest";
                            $insertData['old_value'] = $radioType[$oldContestInfo['publish_contest']];
                            $insertData['new_value'] = $radioType[$contest->publish_contest];                        
                            $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);
                        }
                        
                        $insertData['field_name'] = "Entry Start Date";
                        $insertData['old_value'] = $oldContestInfo['entry_start_date'];
                        $insertData['new_value'] = $contest->entry_start_date;                        
                        $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);
                                
                        $insertData['field_name'] = "Entry End Date";
                        $insertData['old_value'] = $oldContestInfo['entry_end_date'];
                        $insertData['new_value'] = $contest->entry_end_date;                        
                        $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);
                                
                        $insertData['field_name'] = "Winners Announce Date";
                        $insertData['old_value'] = $oldContestInfo['winners_announce_date'];
                        $insertData['new_value'] = $contest->winners_announce_date;
                        $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);
                                
                        $insertData['field_name'] = "Voting Start Date";
                        $insertData['old_value'] = $oldContestInfo['voting_start_date'];
                        $insertData['new_value'] = $contest->voting_start_date;
                        $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);
                        
                        $insertData['field_name'] = "Maximum Number Of Photos";
                        $insertData['old_value'] = $oldContestInfo['max_no_of_photos'];
                        $insertData['new_value'] = $contest->max_no_of_photos;                        
                        $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);
                        
                        if($contest->is_exclusive != '') {
                            $insertData['field_name'] = "Exclusive";
                            $insertData['old_value'] = $radioType[$oldContestInfo['is_exclusive']];
                            $insertData['new_value'] = $radioType[$contest->is_exclusive];
                            $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);
                        }
                        $insertData['field_name'] = "Contest Type";
                        $insertData['old_value'] = $contestType[$oldContestInfo['type_id']];
                        $insertData['new_value'] = $contestType[$contest->type_id];
                        $adminActivityTrackTable->saveAdminTracking($insertData,TRUE); 
                        
                        if ($params['thumbnail'] != 'NOT_UPDATED') {
                            $insertData['field_name'] = "Thumbnail";
                            $insertData['change_type'] = "IMAGE UPDATE";
                            if($oldContestInfo['thumbnail'] == 'NOT_UPDATED') {
                                $insertData['old_value'] = $oldContestInfo['thumbnail'];                                
                            } else {
                                $insertData['old_value'] = $config['aws']['path'].$oldContestInfo['thumbnail'];
                            }                            
                            $insertData['new_value'] =  $config['aws']['path'].$params['thumbnail'];                        
                            $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);                        
                        }
                        
                        if($oldContestInfo['type_id'] == 3) {
                            $insertData['field_name'] = "Round One Date";
                            $insertData['old_value'] = $oldContestInfo['round1'];
                            $insertData['new_value'] = $this->reformatDate($params['br_round1']);
                            $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);

                            $insertData['field_name'] = "Round Two Date";
                            $insertData['old_value'] = $oldContestInfo['round2'];
                            $insertData['new_value'] = $this->reformatDate($params['br_round2']);
                            $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);

                            $insertData['field_name'] = "Round Three Date";
                            $insertData['old_value'] = $oldContestInfo['round3'];
                            $insertData['new_value'] = $this->reformatDate($params['br_round3']);
                            $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);

                            $insertData['field_name'] = "Round Four Date";
                            $insertData['old_value'] = $oldContestInfo['round4'];
                            $insertData['new_value'] = $this->reformatDate($params['br_round4']);
                            $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);

                            $insertData['field_name'] = "Round Five Date";
                            $insertData['old_value'] = $oldContestInfo['round5'];
                            $insertData['new_value'] = $this->reformatDate($params['br_round5']);
                            $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);

                            $insertData['field_name'] = "Round Six Date";
                            $insertData['old_value'] = $oldContestInfo['round6'];
                            $insertData['new_value'] = $this->reformatDate($params['br_round6']);
                            $adminActivityTrackTable->saveAdminTracking($insertData,TRUE);
                            
                        }
                                                
                        $response['success'] = true;
                        $response['message'] = 'Contest updated successfully';
                        
                        $fbscrap->informFbToScrap($params['id']);
                    } else {
                        $contest->thumbnail = $params['thumbnail'];
                        $contest->voting_started = 0;
                        $contest->winners_announced = 0;

                        $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
                        $data = $contestTable->insert($contest);
                        if($params['type'] == '3' && $data) {
                            $params['contest_id'] = $data;
                            
                            $this->insertOrUpdateBracketRounds($params);
                        }
                        
                        $adminActivityTrackTable = $this->getServiceLocator()->get('YagGames\Model\AdminActivityTrackTable');
                        $contest->id = $data;
                        $params['created_at'] = date('Y-m-d H:i:s'); 
                        if($contest->thumbnail != "NOT_UPDATED")
                            $params['thumbnail'] = $config['aws']['path']. $contest->thumbnail;
                        
                        $upperCaseKeyArray = array_change_key_case($params,CASE_UPPER);
                        
                        $insertData = [
                            'admin_id' => $_SESSION['admin_user']['admin_id'],
                            'form_name' => "Manager > Create Contest",
                            'new_value' => json_encode($upperCaseKeyArray),
                            'comment' => "Contest ". $params['name']. " [". $data. "] Created",
                            'change_type' => "NEW RECORD",
                        ];  
                        $adminActivityTrackTable->saveAdminTracking($insertData);  
                        
                        $response['success'] = true;
                        $response['message'] = 'Contest created successfully';

                        $fbscrap->informFbToScrap($data);
                    }
                }
            } else {

                $response['success'] = false;
                $response['message'] = 'BAD REQUEST';
            }
        } else {
            $response['success'] = false;
            $response['message'] = "You don't have enough permissions";
            return new JsonModel($response);
        }

        return new JsonModel($response);
    }

    //deleting some contest
    public function deleteContestAction() {
        $add_contest = 0;
        $delete_contest = 0;
        $review_contest = 0;

        if ($permissions = $_SESSION['admin_user']['permissions']) {

            foreach ($permissions as $permission) {
                if ($permission == 'add-contest') {
                    $add_contest = 1;
                }
                if ($permission == 'delete-contest') {
                    $delete_contest = 1;
                }
                if ($permission == 'review-contest') {
                    $review_contest = 1;
                }
            }
        }

        $response = array();

        //checking for correct permissions
        if ($delete_contest) {

            $request = $this->getRequest();
            if ($request->isPost()) {
                $id = trim($this->getRequest()->getPost('id'));
                $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
                $oldContestInfo = $contestTable->getByContestId($id);
                if ($contestTable->delete($id)) {
                    $adminActivityTrackTable = $this->getServiceLocator()->get('YagGames\Model\AdminActivityTrackTable');                    
                    $data = [
                        'admin_id' => $_SESSION['admin_user']['admin_id'],
                        'form_name' => "Manager > Action > Delete Contest",
                        'comment' => "Contest ". $oldContestInfo['name']. " [". $id. "] Deleted",
                        'change_type' => "OTHER",
                    ];                    
                    $adminActivityTrackTable->saveAdminTracking($data);                    
                    $response['success'] = true;
                    $response['message'] = 'Deleted successfully';
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Some error occured while deleting';
                }
            } else {

                $response['success'] = false;
                $response['message'] = 'BAD REQUEST';
            }
        } else {
            $response['success'] = false;
            $response['message'] = "You don't have enough permissions";
            return new JsonModel($response);
        }
        return new JsonModel($response);
    }

    //removing a media from a contest(wrongly upload by user)
    public function deleteContestMediaAction() {
        $response = array();
      
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params['contest_id'] = trim($this->getRequest()->getPost('contest_id'));
            $params['media_id'] = trim($this->getRequest()->getPost('media_id'));
            $params['umedia_id'] = trim($this->getRequest()->getPost('umedia_id'));
            
            $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
 
            if ($contestMediaTable->delete($params)) {

                $contest = array();
                $config = $this->getConfig();
                $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
                $contest = $contestTable->fetchRecord($params['contest_id']);
                $user_data = $contestTable->getUserInfoByMediaId($params['media_id']);
                $contest['main_site_url'] = $config['main_site']['url'];
                $contest['user_data'] = $user_data;
                //$this->sendEmail('Your image has been disqualified and removed from the ' . $contest['name'], $user_data['email'], 'image_disqualified', $contest);
                
                $oldContestInfo = $contestTable->getByContestId($params['contest_id']);
                $data = [
                    'admin_id' => $_SESSION['admin_user']['admin_id'],
                    'form_name' => "Manager > Contest Name > delete Media",
                    'comment' => "Deleted Media [".$params['umedia_id']."] from contest ".$oldContestInfo['name'],
                    'change_type' => "OTHER",
                ];
                
                $adminActivityTrackTable = $this->getServiceLocator()->get('YagGames\Model\AdminActivityTrackTable');
                $adminActivityTrackTable->saveAdminTracking($data);
                
                $response['success'] = true;
                $response['message'] = 'Removed successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Some error occured while removing';
            }
        } else {

            $response['success'] = false;
            $response['message'] = 'BAD REQUEST';
        }
        return new JsonModel($response);
    }

    private function getContestTypes() {
        $contestTypeTable = $this->getServiceLocator()->get('YagGames\Model\ContestTypeTable');
        $data = $contestTypeTable->fetchAll();
        return $data;
    }

    private function getContests($page) {
        $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
        $data = $contestTable->getAllContests($page);
        return $data;
    }

    private function getContestDetails($id) {
        $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
        $data = $contestTable->fetchRecord($id);
        return $data;
    }

    private function getContestPhotos($id) {
        $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
        $data = $contestMediaTable->fetchAllByContest($id);
        return $data;
    }
    
    private function getUserContestPhotos($id) {
        $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
        $data = $contestMediaTable->fetchAllUserMediaDetailsByContest($id);
        return $data;
    }
    
    private function insertOrUpdateBracketRounds($params){
        $contestBracketRound = new \YagGames\Model\ContestBracketRound();
        
        $contestBracketRound->contest_id = $params['contest_id'];
        $contestBracketRound->round1 = $this->reformatDate($params['br_round1']);
        $contestBracketRound->round2 = $this->reformatDate($params['br_round2']);
        $contestBracketRound->round3 = $this->reformatDate($params['br_round3']);
        $contestBracketRound->round4 = $this->reformatDate($params['br_round4']);
        $contestBracketRound->round5 = $this->reformatDate($params['br_round5']);
        $contestBracketRound->round6 = $this->reformatDate($params['br_round6']);
        
        $contestBracketRoundTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketRoundTable');
        $contestBracketRoundDetails = (array) $contestBracketRoundTable->fetchRecordOnContestId($params['contest_id']);
        if(!isset($contestBracketRoundDetails['id'])) {
            $contestBracketRoundResult = $contestBracketRoundTable->insert($contestBracketRound);
        } else {
            $contestBracketRound->id = $contestBracketRoundDetails['id'];
            $contestBracketRound->created_at = $contestBracketRoundDetails['created_at'];
            $contestBracketRoundResult = $contestBracketRoundTable->update($contestBracketRound);
        }
        
        return $contestBracketRoundResult;
    }
    
    public function publishContestAction() {
        $add_contest = 0;
        $delete_contest = 0;
        $review_contest = 0;
        $publish_contest = 0;

        if ($permissions = $_SESSION['admin_user']['permissions']) {

            foreach ($permissions as $permission) {
                if ($permission == 'add-contest') {
                    $add_contest = 1;
                }
                if ($permission == 'delete-contest') {
                    $delete_contest = 1;
                }
                if ($permission == 'review-contest') {
                    $review_contest = 1;
                }
                if ($permission == 'publish-contest') {
                    $publish_contest = 1;
                }
            }
        }

        $response = array();

        //checking for correct permissions
        if ($publish_contest) {

            $request = $this->getRequest();
            if ($request->isPost()) {
                $id = trim($this->getRequest()->getPost('id'));
                $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
                $contest = new \YagGames\Model\Contest();
                $contest->id = $id;
                $contest->publish_contest = (int)trim($this->getRequest()->getPost('publish_contest')); 
                $oldContestInfo = $contestTable->getByContestId($id);
                if ($contestTable->update($contest)) {                    
                    $adminActivityTrackTable = $this->getServiceLocator()->get('YagGames\Model\AdminActivityTrackTable');

                    $publishContest = [
                        '0' => "Don't Publish",
                        '1' => "Publish"
                    ];
                    $insertData['admin_id'] = $_SESSION['admin_user']['admin_id'];
                    $insertData['form_name'] = "Manager > Publish Contest";
                    $insertData['field_name'] = "Publish Contest";
                    $insertData['comment'] = "Contest ". $oldContestInfo['name'] . "Updated";
                    $insertData['change_type'] = "FIELD UPDATE";
                    $insertData['old_value'] = $publishContest[$oldContestInfo['publish_contest']];
                    $insertData['new_value'] = $publishContest[$contest->publish_contest];

                    $adminActivityTrackTable->saveAdminTracking($insertData, TRUE);

                    $response['success'] = true;
                    if($contest->publish_contest == 1) {
                        $response['message'] = 'Published contest successfully';
                    } else if($contest->publish_contest == 0) {
                        $response['message'] = 'Hidden the contest successfully';
                    }
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Some error occured while deleting';
                }
            } else {

                $response['success'] = false;
                $response['message'] = 'BAD REQUEST';
            }
        } else {
            $response['success'] = false;
            $response['message'] = "You don't have enough permissions";
            return new JsonModel($response);
        }
        return new JsonModel($response);
    }
    
    public function modifyContestRankingsAction()
    {
        $response = array('success' => false, 'message' => 'Something went wrong, try again!');        

        try {
            $this->checkLogin();
            $request = $this->getRequest();
            if ($request->isPost()) {
                if (!empty($_SESSION['admin_user']['permissions']) && in_array('review-contest', $_SESSION['admin_user']['permissions'])) {
                    $contestMediaRatings = $this->getServiceLocator()->get('YagGames\Model\ContestMediaRatingTable');
                    $mediaRankInfo = $contestMediaRatings->getPhotoContestMediaRank($request->getPost('contest_id'), $request->getPost('contest_media_id'));

                    if ($mediaRankInfo) {
                        if ($request->getPost('intended_rank') > $mediaRankInfo['rank']) {
                            $contestRankingsModify = new \YagGames\Model\ContestRankingsModify;
                            $contestRankingsModify->admin_id = $this->session->admin_id;
                            $contestRankingsModify->contest_media_id = $request->getPost('contest_media_id');
                            $contestRankingsModify->intended_rank = $request->getPost('intended_rank');
                            $contestRankingsModify->status = 1;
                            
                            $contestRankingsModifyTable = $this->getServiceLocator()->get('YagGames\Model\ContestRankingsModifyTable');
                            $contestRankingsRecord = $contestRankingsModifyTable->fetchRecordByMediaId($request->getPost('contest_media_id'));
                           
                            if ($contestRankingsRecord) {
                                $contestRankingsModify->id = $contestRankingsRecord->id;
                                $insertedId = $contestRankingsModifyTable->update($contestRankingsModify);
                            } else {
                                $insertedId = $contestRankingsModifyTable->insert($contestRankingsModify);
                            }                            

                            if ($insertedId) {
                                $response['success'] = true;
                                $response['message'] = 'Success';
                            }
                        } else {
                            $response['message'] = "Current rank of this art is {$mediaRankInfo['rank']}, intended rank should be lowest of this.";                            
                        }
                    }
                } else {
                    $response['message'] = "You don't have enough permissions";
                }
            }
        } catch (Exception $ex) {
            
        }

        return new JsonModel($response);
    }

    public function removeIntendedRankAction()
    {
        $response = array('success' => false, 'message' => 'Something went wrong, try again!');

        try {
            $this->checkLogin();
            $request = $this->getRequest();
            if ($request->isPost()) {
                if (!empty($_SESSION['admin_user']['permissions']) && in_array('review-contest', $_SESSION['admin_user']['permissions'])) {
                    $contestRankingsModifyTable = $this->getServiceLocator()->get('YagGames\Model\ContestRankingsModifyTable');
                    $contestRankingsModifyTable->inActivateByMediaId($request->getPost('contest_media_id'));
                    $response['success'] = true;
                    $response['message'] = 'Success';                    
                } else {
                    $response['message'] = "You don't have enough permissions";
                }
            } else {
                
            }
        } catch (Exception $ex) {
            
        }

        return new JsonModel($response);
    }

    /**
     * 
     * @param String $date - Input Date format MM-DD-YYYY
     * @return String - Date format YYYY-MM-DD
     */
    private function reformatDate($date)
    {
        $d = \DateTime::createFromFormat("m-d-Y", $date);
        
        return $d->format("Y-m-d");
    }

}
