<?php

// #######################
// Edit DB config
// #######################

$host     = 'localhost';
$dbname   = 'kayako';
$username = 'root';
$password = '';

// #######################

try {
    $connection = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully\n";

    $limit  = 1000;
    $offset = 0;

    do {
        $statement = $connection->query("SELECT attachmentid, storefilename FROM swattachments LIMIT $offset, $limit");
        $results   = $statement->fetchAll();
        foreach ($results as $attachment) {
            $attachmentId = $attachment['attachmentid'];
            $filename     = trim($attachment['storefilename']);

            if (!$filename) {
                echo "No filename for attachment $attachmentId\n";
                continue;
            }

            $filepath = __DIR__.DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'__swift'.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.$filename;
            if (!file_exists($filepath)) {
                echo "Unable to load file $filepath\n";
                continue;
            }

            $content = file_get_contents($filepath);
            if (!$content) {
                echo "Unable to get content of $filepath\n";
                continue;
            }

            try {
                $delete = $connection->prepare('DELETE FROM swattachmentchunks WHERE attachmentid = ?');
                $delete->execute([$attachment['attachmentid']]);
            } catch (\Exception $e) {
                echo $e->getMessage()."\n";
                continue;
            }

            try {
                $insert = $connection->prepare('INSERT IGNORE INTO swattachmentchunks (attachmentid, contents, notbase64) VALUES (?, ?, ?)');
                $insert->execute([$attachment['attachmentid'], $content, 1]);
            } catch (\Exception $e) {
                echo $e->getMessage()."\n";
            }
        }

        $offset += $limit;
    } while (count($results) > 0);
} catch(PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage()."\n";
}
