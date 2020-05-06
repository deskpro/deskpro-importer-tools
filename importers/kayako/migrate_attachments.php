<?php

if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    echo "Unable to load the config file, config.php is missing\n";
    echo "It should be placed in the same directory as the migration script.\n";
    exit;
}

$host     = @$CONFIG['dbinfo']['host'];
$port     = @$CONFIG['dbinfo']['port'];
$dbname   = @$CONFIG['dbinfo']['dbname'];
$username = @$CONFIG['dbinfo']['user'];
$password = @$CONFIG['dbinfo']['password'];

try {
    $connection = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
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

            try {
                $connection->beginTransaction();

                $delete = $connection->prepare('DELETE FROM swattachmentchunks WHERE attachmentid = ?');
                $delete->execute([$attachment['attachmentid']]);

                $i  = 1;
                $fp = fopen($filepath,'r');
                while (!feof($fp)) {
                    $content = fread($fp,1000000);

                    try {
                        $insert = $connection->prepare('INSERT IGNORE INTO swattachmentchunks (attachmentid, contents, notbase64) VALUES (?, ?, ?)');
                        $insert->execute([$attachment['attachmentid'], $content, 1]);
                    } catch (\Exception $e) {
                        echo $e->getMessage()."\n";
                    }

                    $i++;
                }

                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollBack();
                echo $e->getMessage()."\n";
            }
        }

        $offset += $limit;
    } while (count($results) > 0);
} catch(PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage()."\n";
}
