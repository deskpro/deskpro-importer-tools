<?php

########################################################################################################################
# CONFIG
########################################################################################################################

require __DIR__.'/config.php';

########################################################################################################################
# Do not edit below this line
########################################################################################################################

$reader = \DeskPRO\ImporterTools\Importers\ZenDesk\ZenDeskReader::createReader($CONFIG['account']);
$output = \DeskPRO\ImporterTools\Helpers\OutputHelper::getHelper();
$writer = \DeskPRO\ImporterTools\Helpers\WriteHelper::getHelper();

//--------------------
// Organizations
//--------------------

$output->startSection('Organizations');
foreach ($reader->getOrganizations() as $organization) {
    $writer->writeOrganization($organization['id'], [
        'name'         => $organization['name'],
        'date_created' => $organization['created_at'],
        'labels'       => $organization['tags'],
    ]);
}

//--------------------
// People
//--------------------

$output->startSection('People');
$pager = $reader->getPersonPager(new \DateTime($CONFIG['start_time']));
while ($people = $pager->getNext()) {
    foreach ($people as $person) {
        $writer->writePerson($person['id'], [
            'name'   => $person['name'],
            'emails' => [$person['email']],
        ]);
    }
}

//--------------------
// Tickets
//--------------------