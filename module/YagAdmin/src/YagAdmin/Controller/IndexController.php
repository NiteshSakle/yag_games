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

class IndexController extends BaseController {

    public function indexAction() {
        $this->checkLogin();

        $types = array();
        $contests = array();

        $types = $this->getContestTypes();
        $contests = $this->getContests();

        return new ViewModel(array('types' => $types, 'contests' => $contests));
    }

    public function contestDetailsAction() {
        $this->checkLogin();

        $contestDetails = array();
        $contestPhotos = array();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = trim($this->getRequest()->getPost('id'));

            $contestDetails = $this->getContestDetails($id);
            $contestPhotos = $this->getContestPhotos($id);
        } else {
            $this->redirect()->toRoute('admin');
        }

        return new ViewModel(array('contestDetails' => $contestDetails, 'contestPhotos' => $contestPhotos));
    }

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

    public function saveContestAction() {
        $this->checkLogin();

        $response = array();

        $request = $this->getRequest();
        if ($request->isPost()) {
            
            $params['id'] = trim($this->getRequest()->getPost('id'));
            $params['name'] = trim($this->getRequest()->getPost('name'));
            $params['description'] = $this->getRequest()->getPost('description');
            $params['entryEndDate'] = $this->getRequest()->getPost('entryEndDate');
            $params['winnersAnnounceDate'] = $this->getRequest()->getPost('winnersAnnounceDate');
            $params['votingStartDate'] = $this->getRequest()->getPost('votingStartDate');
            $params['entryLimit'] = $this->getRequest()->getPost('entryLimit');
            $params['type'] = $this->getRequest()->getPost('type');
            $params['exclusive'] = $this->getRequest()->getPost('exclusive');
            $thumbnail = $this->getRequest()->getFiles('thumbnail');
            if (($thumbnail['error'] == 4 || $thumbnail['size'] == 0) && isset($params['id'])) {
                $params['thumbnail'] = 'NOT_UPDATED';
            } else {
                $fileType = $thumbnail['type'];
                $allowedImageTypes = array("image/pjpeg", "image/jpeg", "image/jpg", "image/png", "image/x-png", "image/gif");
                if ($thumbnail['error'] == 4 || $thumbnail['size'] == 0 || !in_array($thumbnail['type'], $allowedImageTypes)) {
                    $response['success'] = false;
                    $response['message'] = 'Please attach a valid image';

                    return new JsonModel($response);
                } else {

                    $name = strtotime("now") . $thumbnail['name'];
                    $config = $this->getConfig();
                    $destination = $config['upload_path'] . $name;

                    if (move_uploaded_file($thumbnail['tmp_name'], $destination)) {

                        $pathToS3File = "contest/" . $name;

                        $aws_key = $config['aws']['key'];
                        $aws_secret = $config['aws']['secret'];
                        $bucket = $config['aws']['bucket'];
                        $version = $config['aws']['version'];
                        $region = $config['aws']['region'];

                        $s3Client = S3Client::factory(array(
                                    'credentials' => array(
                                        'key' => $aws_key,
                                        'secret' => $aws_secret,
                                    ),
                                    'region' => $region,
                                    'version' => $version
                        ));

                        try {
                            $s3Client->putObject([
                                'Bucket' => 'yagdev',
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

            if (empty($params['name']) || empty($params['description']) || empty($params['entryEndDate']) || empty($params['winnersAnnounceDate']) || empty($params['votingStartDate']) || empty($params['entryLimit']) || empty($params['type']) || empty($params['exclusive']) || empty($params['thumbnail'])) {

                $response['success'] = false;
                $response['message'] = 'Please fill in all fields';
            } else {

                $contest = new \YagGames\Model\Contest();

                $contest->name = $params['name'];
                $contest->description = $params['description'];
                $contest->entry_end_date = date('Y-m-d', strtotime($params['entryEndDate']));
                $contest->winners_announce_date = date('Y-m-d', strtotime($params['winnersAnnounceDate']));
                $contest->voting_start_date = date('Y-m-d', strtotime($params['votingStartDate']));
                $contest->max_no_of_photos= $params['entryLimit'];
                $contest->is_exclusive= $params['exclusive'];
                $contest->type_id = $params['type'];

                if ($params['id']) {
                    $contest->id = $params['id'];
                    if ($params['thumbnail'] != 'NOT_UPDATED') {
                        $contest->thumbnail = $params['thumbnail'];
                    }

                    $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
                    $data = $contestTable->update($contest);

                    $response['success'] = true;
                    $response['message'] = 'Contest updated successfully';
                } else {
                    $contest->thumbnail = $params['thumbnail'];

                    $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
                    $data = $contestTable->insert($contest);

                    $response['success'] = true;
                    $response['message'] = 'Contest created successfully';
                }
            }
        } else {

            $response['success'] = false;
            $response['message'] = 'BAD REQUEST';
        }

        return new JsonModel($response);
    }

    public function deleteContestAction() {
        $response = array();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = trim($this->getRequest()->getPost('id'));
            $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
            if ($contestTable->delete($id)) {
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
        return new JsonModel($response);
    }

    public function deleteContestMediaAction() {
        $response = array();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $params['contest_id'] = trim($this->getRequest()->getPost('contest_id'));
            $params['media_id'] = trim($this->getRequest()->getPost('media_id'));
            $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
            if ($contestMediaTable->delete($params)) {
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

    private function getContests() {
        $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
        $data = $contestTable->fetchAll();
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

}
