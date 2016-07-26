<?php
/*
 * This tool is a half-completed script that will open a CSV file.
 *
 * 1) Edit the values in the CONFIG section below.
 *
 * 2) You will need custom code in the "CUSTOM CODE" section to handle what to do with each row of your file.
 *
 * 3) Run this script from the command-line:
 *
 *     $ cd /path/to/deskpro-importer-tools
 *     $ php csv.php
 *
 * 3) Run the DeskPRO import script
 *     $ cd /path/to/deskpro
 *     $ php cmd.php dp:import:run json --input-path "/path/to/deskpro-importer-tools/data"
 */

########################################################################################################################
# CONFIG
########################################################################################################################

$CONFIG = array();

/**
 * Enter the full path to your CSV file.
 */
$CONFIG['csv_file_path'] = '/path/to/my-file.csv';

/**
 * Set to TRUE if your CSV file has a header row,
 * set to FALSE otherwise.
 *
 * A header row is a row of non-data that other applications
 * such as Excel might use as column headers.
 */
$CONFIG['has_header_row'] = true;

/**
 * Set the CSV delimiter character. Commonly: , (comma) or ; (semi-colon)
 */
$CONFIG['delimiter'] = ',';

/**
 * Set the enclosure character that surrounds complex strings. Commonly: " (double-quote), ' (single-quote)
 */
$CONFIG['enclosure'] = '"';

/**
 * Set the escape character that escapes the enclosure character within strings. Commonly: \ (slash), ' (single-quote)
 */
$CONFIG['escape'] = '\\';

/**
 * If you know, enter the charset that your text is in. DeskPRO converts input into UTF-8, so
 * the tool must know which base charset your data is currently in.
 */
$CONFIG['text_charset'] = 'UTF-8';



########################################################################################################################
# CUSTOM CODE
########################################################################################################################

/**
 * $row is a single row of your CSV file. Write data using the $writer;
 *
 * $row is keyed numerically, with 0 being the first column, 1 being the second, etc.
 * If your CSV file defiend a header row, then $row will ALSO contain string
 * keys, where the keys are the titles from the header row.
 *
 * @param array $row
 * @param \DpTools\ImportWriter\Writer $writer
 */
function write_record_from_row(array $row, $writer)
{
    // Your code goes here. Here's an example:

    $writer->person(array(
        'name' => $row['User Name'],
        'emails' => array($row['User Email'])
    ), true);

    $date_created = \DateTime::createFromFormat('Y-m-d', $row['Date']);

    $messages = array();
    $messages[] = array(
        // the ID is any unique string. if you dont have a specific message ID,
        // you can just use a hash of some uniuqe data, such as the ticket ID and the text of the message
        'id' => md5($row['ID'] . $row['Question']),
        'person' => $row['User Email'],
        'message_text' => $row['Question'],
        'date_created' => $date_created->format('Y-m-d H:i:s'),
    );

    $writer->ticket(array(
        'id' => $row['ID'],
        'ref' => 'ABC-' . $row['ID'],
        'status' => 'awaiting_agent',
        'user' => $row['User Email'],
        'subject' => $row['Subject'] ?: '(no subject)',
        'department' => $row['Department'],
        'date_created' => $date_created->format('Y-m-d H:i:s'),
        'messages' => $messages
    ));
}

/**
 * This accepts an array of 1000 rows. You can optionally override this function
 * with custom code to specially handle how to write a batch. But usually
 * you can just leave this default and just customise write_record_from_row() above.
 *
 * @param array $rows
 * @param \DpTools\ImportWriter\Writer $writer
 */
function write_batch(array $rows, $writer)
{
    foreach ($rows as $r) {
        write_record_from_row($r, $writer);
        echo ".";
    }
}



########################################################################################################################
# Do not edit below this line
########################################################################################################################

require 'src/inc.php';

if (empty($CONFIG['csv_file_path']) || !is_file($CONFIG['csv_file_path']) || !is_readable($CONFIG['csv_file_path'])) {
    echo "Invalid file: {$CONFIG['csv_file_path']}\n";
    exit(1);
}

$fp = fopen($CONFIG['csv_file_path'], 'r') or die("Could not open file for reading\n");

$writer = create_writer();
$writer->enableBatchedMode();

$count = 0;
$batch = array();
$keys = array();

while (($line = fgetcsv($fp, 0, $CONFIG['delimiter'], $CONFIG['enclosure'], $CONFIG['escape']))) {
    $count++;
    if ($count === 1 && $CONFIG['has_header_row']) {
        $keys = $line;
        continue;
    }

    $line = array_map('clean_text', $line);

    // Copies data into named keys if we had a header row
    if ($keys) {
        foreach ($keys as $idx => $k) {
            if (isset($line[$idx])) {
                $line[$k] = $line[$idx];
            }
        }
    }

    $batch[] = $line;
    if ($count % $writer->getBatchSize() === 0) {
        write_batch($batch, $writer);
        $batch = array();
        echo $count;
    }
}

echo "\n\nDone\n";
