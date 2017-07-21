<?php
namespace YagGames\Console;

use Zend\Console\Request as ConsoleRequest;

set_time_limit(3600);
class ModifyRankingsController extends BaseConsoleController
{

    public function indexAction()
    {
        $request = $this->getRequest();

        // Make sure that we are running in a console and the user has not tricked our
        // application into running this action from a public web server.
        if (!$request instanceof ConsoleRequest) {
            throw new \RuntimeException('You can only use this action from a console!');
        }

        $this->logger = $this->getServiceLocator()->get('YagGames\Logger');

        $this->process();

        echo "Done\n";
    }

    private function process()
    {
        $contestRankingsModifyTable = $this->getServiceLocator()->get('YagGames\Model\ContestRankingsModifyTable');
        $records = $contestRankingsModifyTable->fetchActiveRecords();

        if (count($records)) {
            $contestRankingsProcessedTable = $this->getServiceLocator()->get('YagGames\Model\ContestRankingsProcessedTable');

            $contestMediaRatingTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaRatingTable');
            foreach ($records as $contestMediaRatingId => $row) {
                // Get current rank of the media
                $mediaRankInfo = $contestMediaRatingTable->getPhotoContestMediaRank($row['contest_id'], $contestMediaRatingId);
                $processedRecord = new \YagGames\Model\ContestRankingsProcessed();
                $processedRecord->contest_rankings_modify_id = $row['id'];
                $processedRecord->before_rank = $mediaRankInfo['rank'];
                if ($mediaRankInfo['rank'] < $row['intended_rank']) {
                    $currentRank = $mediaRankInfo['rank'];                   
                    while ($currentRank < $row['intended_rank']) {
                        $affectedRows = $contestMediaRatingTable->reduceRank($row['contest_media_id'], 5);
                        $newRankInfo = $contestMediaRatingTable->getPhotoContestMediaRank($row['contest_id'], $contestMediaRatingId);
                        $currentRank = $newRankInfo['rank'];
                        if ($affectedRows == 0) {
                            $currentRank = $mediaRankInfo['rank']; // to break the loop
                            $processedRecord->comments = 'Moving to further lowest rank is not possible';                            
                        }                        
                    }
                    $processedRecord->after_rank = $currentRank;
                    $processedRecord->processed = 1;                    
                    $contestRankingsProcessedTable->insert($processedRecord);
                } else {
                    $processedRecord->after_rank = $mediaRankInfo['rank'];
                    $processedRecord->processed = 0;
                    $processedRecord->comments = 'Actual rank is lower than or equal to intended rank';
                    $contestRankingsProcessedTable->insert($processedRecord);
                }
            }
        }
    }

}