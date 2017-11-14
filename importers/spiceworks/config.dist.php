<?php

$DP_CONFIG = [];

/**
 * Enter the full path to your SpiceWorks SQLite database file.
 */
$DP_CONFIG['db_path'] = '/path/to/spiceworks.db';

/**
 * Enter the full path to your SpiceWorks ticket attachments directory.
 * The data directory should be: <path to SpiceWorks>/data/uploads/Ticket
 *
 * This directory should contain many sub-directories. Each directory is
 * a ticket ID, and inside each directory will be the file attachments
 * on the ticket.
 */
$DP_CONFIG['ticket_attachments_path'] = '/path/to/spiceworks/data/uploads/Ticket';
