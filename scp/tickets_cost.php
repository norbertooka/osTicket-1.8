<?php
require('staff.inc.php');
require_once(INCLUDE_DIR.'class.ticket.php');

function formatTime($time) {
	$hours = floor($time / 60);
	$minutes = $time % 60;

	$formatted = '';

	if ($hours > 0) {
		$formatted .= $hours . ' Hour';
		if ($hours > 1) {
			$formatted .= 's';
		}
	}
	if ($minutes > 0) {
		if ($formatted) $formatted .= ', ';
			$formatted .= $minutes . ' Minute';
		if ($minutes > 1) {
			$formatted .= 's';
		}
	}
	return $formatted;
}

function convTimeType($typeid) {
	$sql = 'SELECT `value` FROM `ost_list_items` WHERE `id` = '. $typeid;
	$res = db_query($sql);
	
	$typearray = db_fetch_array($res);

	$typetext = $typearray['value'];
	return $typetext;
}

function countTime($ticketid, $typeid) {
	$sql = 'SELECT SUM(`time_spent`) AS `totaltime` FROM `ost_ticket_thread` WHERE `ticket_id` = '. $ticketid .' AND `time_type` = '. $typeid;
	$res = db_query($sql);
	
	$timearray = db_fetch_array($res);

	$totaltime = $timearray['totaltime'];
	return $totaltime;
}


$ticket = $user = null; //clean start.
//LOCKDOWN...See if the id provided is actually valid and if the user has access.
if($_REQUEST['id']) {
    if(!($ticket=Ticket::lookup($_REQUEST['id'])))
         $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('ticket'));
    elseif(!$ticket->checkStaffAccess($thisstaff)) {
        $errors['err']=__('Access denied. Contact admin if you believe this is in error');
        $ticket=null; //Clear ticket obj.
    }
}

//Navigation & Page Info
$nav->setTabActive('tickets');
$ost->setPageTitle(sprintf(__('Ticket #%s Billing Report'),$ticket->getNumber()));


if(!$errors) {
	// Retrieve Ticket Information
	$TicketID = $_GET['id'];
	$Subject = $ticket->getSubject();
	$TicketNo = $ticket->getNumber();


	// Determine ID value for time-type
	$sql = 'SELECT * FROM `ost_list` WHERE `type` = "time-type"';
	$res = db_query($sql);
	$timelist = db_fetch_array($res);
	$timelistid = $timelist['id'];


	// Generate Array of times for summary
	$sql = 'SELECT * FROM `ost_list_items` where `list_id` = ' . $timelistid;
	$res = db_query($sql);
	$loop = 0;
	while($row = db_fetch_array($res, MYSQL_ASSOC)) {
		$loop++;
		$time[$loop][0] = countTime($TicketID, $row['id']);
		$time[$loop][1] = $row['id'];
	}
}

require_once(STAFFINC_DIR.'header.inc.php');

if(!$errors) {
?>

	<h1>Billing Report</h1>
	
	<h2>Ticket Information</h2>
	<p><b>Ticket:</b> #<?php echo $TicketNo; ?> <br />
		<b>Subject:</b> <?php echo $Subject; ?> <br />
		<b>Generated:</b> <?php echo date("D M d, Y G:i a"); ?>
	</p>
	<p>&nbsp;</p>
	
	<h2>Time Summary</h2>
	<p>
		<?php
		for ($x = 1; $x <= count($time); $x++) {
			if ($time[$x][0] <> "" && $time[$x][0] > 0) {
				echo formatTime($time[$x][0]) . " " . convTimeType($time[$x][1]) . "<br />";
			}
		} 
		?>
	</p>
	<p>&nbsp;</p>
	
	<h2>Time History / Detail</h2>
	<table border="2">
		<tr>
			<th>Date / Time</th>
			<th>Post Type</th>
			<th>Poster</th>
			<th>Time Spent</th>
			<th>Time Type</th>
			<th>Billable</th>
		</tr>
		<?php
			$sql = 'SELECT * FROM `ost_ticket_thread` WHERE `ticket_id` = ' . $TicketID . ' AND (`thread_type`="R" OR `thread_type`="N")';
			$res = db_query($sql);
			while($row = db_fetch_array($res, MYSQL_ASSOC)) {
				if ($row['poster']<>"SYSTEM") {
					echo '<tr>';
						echo "<td>" . $row['created'] . "</td>";
						if ($row['thread_type']=="R") {
							echo "<td>Response to Customer</td>";
						}
						if ($row['thread_type']=="N") {
							echo "<td>Internal Note</td>";
						}
						echo "<td>" . $row['poster'] . "</td>";
						echo "<td>" . formatTime($row['time_spent']) . "</td>";
						echo "<td>" . convTimeType($row['time_type']) . "</td>";
						echo "<td>";
							if ($row['time_bill']==1) {
								echo "YES";
							} else {
								echo "NO";
							}
						echo "</td>";
					echo '</tr>';
				}
			}
		?>
	</table>
	
<?php
} else {
?>
	<h1>Billing Report</h1>
	<p>You do not have access to this report.</p>
<?php
}
require_once(STAFFINC_DIR.'footer.inc.php');
?>