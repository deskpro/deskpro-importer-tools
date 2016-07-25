<?php

/*
 * This tool will connect to your Kayako database and export your data into the data/ directory in
 * the standard DeskPRO Import Format.
 *
 * After this tool completes, you will run the standard DeskPRO import process to save the data to
 * your live helpdesk. Refer to the README for more details.
 *
 * 1) Edit the values in the CONFIG section below.
 *
 * 2) Copy this script in to your DeskPRO bin directory. For example:
 *
 *     $ cp kayako /path/to/deskpro/bin/importers
 *
 * 2) Run the import process to fetch all of your data from Kayako:
 *
 *     $ cd /path/to/deskpro
 *     $ bin/import kayako
 *
 * 3) You can now optionally verify the integrity of your data:
 *
 *     $ bin/import verify
 *
 * 4) When you're ready, go ahead and apply the import to your live database:
 *
 *     $ bin/import apply
 *
 * 4) And finally, you can clean up the temporary data files from the filesystem:
 *
 *     $ bin/import clean
 *
 */
########################################################################################################################
# CONFIG
########################################################################################################################

require __DIR__.'/config.php';


########################################################################################################################
# Do not edit below this line
########################################################################################################################

//--------------------
// Setup
//--------------------

/** @var \Application\ImportBundle\ScriptHelper\OutputHelper $output */
/** @var \Application\ImportBundle\ScriptHelper\WriteHelper $writer */
/** @var \Application\ImportBundle\ScriptHelper\FormatHelper $formatter */
/** @var \Application\ImportBundle\ScriptHelper\DbHelper $db */

$db->setCredentials($CONFIG['dbinfo']);

//--------------------
// Organizations
//--------------------

$output->startSection('Organizations');
$pager = $db->getPager('SELECT * FROM swuserorganizations');
while ($data = $pager->next()) {
    foreach ($data as $n) {
        $organization = [
            'name' => $n['organizationname'],
        ];

        // set organization contact data
        // website
        if ($formatter->getFormattedUrl($n['website'])) {
            $organization['contact_data']['website'][] = [
                'url' => $formatter->getFormattedUrl($n['website']),
            ];
        }

        // phone numbers
        if ($formatter->getFormattedNumber($n['phone'])) {
            $organization['contact_data']['phone'][] = [
                'number' => $formatter->getFormattedNumber($n['phone']),
                'type'   => 'phone',
            ];
        }
        if ($formatter->getFormattedNumber($n['fax'])) {
            $organization['contact_data']['phone'][] = [
                'number' => $formatter->getFormattedNumber($n['fax']),
                'type'   => 'fax',
            ];
        }

        // address
        if ($n['address']) {
            $organization['contact_data']['address'][] = [
                'address' => $n['address'],
                'city'    => $n['city'],
                'zip'     => $n['postalcode'],
                'state'   => $n['state'],
                'country' => $n['country'],
            ];
        }

        $writer->writeOrganization($n['userorganizationid'], $organization);
    }
}

//--------------------
// Staff and users
//--------------------

$output->startSection('People');
$pager = $db->getPager('SELECT * FROM swstaff');
while ($data = $pager->next()) {
    foreach ($data as $n) {
        $writer->writePerson('agent_'.$n['staffid'], [
            'name'        => $n['fullname'],
            'emails'      => [$n['email']],
            'is_disabled' => !$n['isenabled'],
            'is_agent'    => true,
        ]);
    }
}

$pager = $db->getPager('SELECT * FROM swusers');
while ($data = $pager->next()) {
    foreach ($data as $n) {
        $person = [
            'name'         => $n['fullname'],
            'emails'       => ['imported.user.' . $n['userid'] . '@example.com'],
            'is_disabled'  => !$n['isenabled'],
            'organization' => $n['userorganizationid'],
        ];

        if ($person['organization']) {
            $person['organization_position'] = $n['userdesignation'];
        }

        if ($formatter->getFormattedNumber($n['phone'])) {
            $person['contact_data']['phone'][] = [
                'number' => $formatter->getFormattedNumber($n['phone']),
                'type'   => 'phone',
            ];
        }

        $writer->writePerson('user_'.$n['userid'], $person);
    }
}

//--------------------
// Tickets and messages
//--------------------

$output->startSection('Tickets');
$pager = $db->getPager('SELECT * FROM swtickets');

$statusMapping = [
    'Open'        => 'awaiting_agent',
    'In Progress' => 'awaiting_agent',
    'Closed'      => 'resolved',
];

while ($data = $pager->next()) {
    foreach ($data as $n) {
        $ticket = [
            'subject'    => $n['subject'],
            'person'     => $n['userid'] ? 'user_'.$n['userid'] : null,
            'agent'      => $n['staffid'] ? 'agent_'.$n['staffid'] : null,
            'department' => $n['departmenttitle'],
            'status'     => $statusMapping[$n['ticketstatustitle']],
        ];

        $messagePager = $db->getPager('SELECT * FROM swticketposts WHERE ticketid = :ticket_id', [
            'ticket_id' => $n['ticketid'],
        ]);

        while ($messageData = $messagePager->next()) {
            foreach ($messageData as $m) {
                $ticket['messages'][] = [
                    'oid'     => $m['ticketpostid'],
                    'person'  => $m['userid'],
                    'message' => $m['contents'],
                ];
            }
        }

        $writer->writeTicket($n['ticketid'], $ticket);
    }
}
