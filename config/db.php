<?php //This file is used for Making Connection to the Database and Contains some of the Generic Function ?>
<?php
        
	//define("DIHOST", "daldb.vpsdime.com");
	define("DIHOST","104.251.215.254");
	define("DIUSER", "huaga_touyara");
	define("DIPASS", "tack2016hgt");
	define("DIDB", "huaga_huagathi");
	
	// $conn = @mysql_connect(DIHOST,DIUSER,DIPASS) or die('Connection Error:');
	// mysql_select_db(DIDB,$conn);
	
	//echo convert_time_zone('2013-04-05 12:40:21', 'Europe/London', $ret_format = 'Y-m-d H:i:s', $from_tz = 'GMT');
	//date_default_timezone_set("Asia/Kolkata");
	// Setting the Timezone to Asia/Calcutta, for storing dates and time in Indian format.
	// putenv("TZ=Asia/Kolkata");
	date_default_timezone_set( "Asia/Bangkok" );
	define("DATE_TIME", "Y-m-d H:i:s");
	define("DATE_TIME_TRANS", "YmdHis");
	define("DATE", "Y-m-d");
	define("TIME", "H:i:s");
	define("DISP_DATE", "d.m.Y");
	define("DISP_TIME", "d-M-Y h:i:s");
	define("DUMP_BKUP_DAYS",30);
	define("MAX_LOGIN_TRY",3);
	define("LOG_DIR", 'log/');
	define("CURRENT_TIME", date(DATE_TIME));
	define("TODAY", date(DATE));
	define("CAPTURE_PATH", '../track/assets/captured/');
	
	
	$rs_array = array(
				"A" => "Vehicle Running Normal",
				"B" => "Vehicle Running in Excess Speed",
				"C" => "Setback to normal Speed",
				"D" => "Excess Speed Start",
				"E" => "Vehicle in stop mode Indication",
				"F" => "Motion Start",
				"G" => "Motion Stop Event",
				"H" => "GPS Fixed First Time After Reset",
				"I" => "Initialize or Reset",
				"J" => "Ignition Status Change",
				"K" => "Box Open",
				"L" => "Transit in External Power",
				"M" => "Message Button pressed",
				"N" => "SOS (Emergency button) pressed",
				"O" => "Change in Digital INPUT State",
				"P" => "Response to Immediate Position Command",
				"Q" => "Response to Parameter change command with Value",
				"R" => "Response to Various parameter query with Value",
				"S" => "Harsh Break",
				"T" => "Immobilized",
				"U" => "GPS Failed",
				"Z" => "Error");
	
	$event_array = array(
				1 => "Input 1 Active (SOS pressed)",
				2 => "Input 2 Active",
				3 => "Input 3 Active",
				4 => "Input 4 Active",
				5 => "Input 5 Active",
				9 => "Input 1 Inactive(SOS released)",
				10 => "Input 2 Inactive",
				11 => "Input 3 Inactive",
				12 => "Input 4 Inactive",
				13 => "Input 5 Inactive",
				17 => "Low Battery",
				18 => "Low External Power",
				19 => "Speeding",
				20 => "Enter Geo-fence",
				21 => "Exit Geo-fence",
				22 => "External Power On",
				23 => "External Power Off",
				24 => "No GPS Signal",
				25 => "Get GPS Signal",
				26 => "Enter Sleep",
				27 => "Exit Sleep",
				28 => "GPS Antenna Cut",
				29 => "Device Reboot",
				30 => "Impact",
				31 => "Heartbeat Report",
				32 => "Heading Change Report",
				33 => "Distance Interval Report",
				34 => "Current Location Report",
				35 => "Time Interval Report",
				36 => "Tow Alarm",
				37 => "RFID",
				39 => "Picture",
				65 => "Press Input 1 (SOS) to Call",
				66 => "Press Input 2 to Call",
				67 => "Press Input 3 to Call",
				68 => "Press Input 4 to Call",
				69 => "Press Input 5 to Call",
				70 => "Reject Incoming Call",
				71 => "Report Location after Incoming Call",
				72 => "Auto Answer Incoming Call",
				73 => "Listen-in (voice monitoring)",
				129 => "Rush Decelerate Alarm",
				130 => "Rush Accelerate Alarm",
				131 => "RPM Over Speed Alarm",
				132 => "RPM Recovery to Normal from Speeding Alarm",
				133 => "Ignition on when Parking Overtime Alarm",
				134 => "'Ignition on when Parking Overtime' Recovery Alarm ( Ignition off or Car Runs again)",
				135 => "Fatigue Driving Alarm",
				136 => "Overtime Rest after Fatigue Driving Alarm",
				137 => "Engine Overheat Alarm",
				138 => "Speed Recovery to Normal Alarm",
				139 => "Maintenance Alarm",
				140 => "Engine Error Alarm",
				141 => "Ready Status Error Alarm",
				142 => "Health Inspect Alarm",
				143 => "Low Fuel Alarm",
				144 => "Ignition On Alarm",
				145 => "Ignition Off Alarm",
				146 => "Car Starts Alarm",
				147 => "Car Stops Alarm",
			);

	function runBackUp()
	{

		$db_auth	= " --host=\"".DIHOST."\" --user=\"".DIUSER."\" --password=\"".DIPASS."\"";
		
		$BACKUP_DEST = "../track/bkup";
		$db = DIDB;
		$dump = "mysqldump";
		$ignore = " --ignore-table=$db.tbl_track --ignore-table=$db.tbl_track_log   --ignore-table=$db.mst_city  --ignore-table=$db.mst_country   --ignore-table=$db.mst_state ";
		$file = $db."_".date("d.m.Y");
		
		if(file_exists("$BACKUP_DEST/$file.sql")){
			
			return;

		}
		// dump db
		unset( $output );
		exec( "$dump $db_auth --opt $ignore $db 2>&1 > $BACKUP_DEST/$file.sql", $output, $res);
		
		if( $res > 0 ) {
			//die( "DUMP FAILED\n".implode( "\n", $output) );
		} else {
			$dir_handle = opendir($BACKUP_DEST) or kill_script("Unable to open $BACKUP_DEST");
			
			while ($file = readdir($dir_handle)) 
			{
				if($file != "." && $file != ".."){
					$filetime = filemtime("$BACKUP_DEST/$file");
					$days = timeBetween(date(DATE_TIME),date(DATE_TIME,$filetime), 3);
					// print("<br />$file modification time " . date(DISP_TIME, $filetime) . ", $days Days OLD");
					if($days > DUMP_BKUP_DAYS){
						unlink("$BACKUP_DEST/$file");
					}
				}
			}
			return;
		}
	}

	
?>