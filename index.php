<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Versidyne Service Status</title>
		<link href="css/bootstrap.min.css" rel="stylesheet">
		<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>
	<body>
		<div class="container">
			<ul class="nav nav-pills nav-stacked col-md-3">
				<li class="active"><a href="#tab_a" data-toggle="pill">Service Status</a></li>
				<li><a href="#tab_b" data-toggle="pill">Job Queue</a></li>
				<li><a href="#tab_c" data-toggle="pill">Minecraft Server Information</a></li>
				<li><a href="#tab_d" data-toggle="pill">Minecraft Crash Report</a></li>
			</ul>
			<div class="tab-content col-md-9">
				<div class="tab-pane active" id="tab_a">
					<h4>Service Status</h4>
					<?php
						// network information
						$host = 'localhost';
						$ports = array(22, 25, 80, 3306, 7900, 25565);
						// initiate loop
						foreach ($ports as $port) {
							// wipe array
							$info = array();
							// assign port
							$info["port"] = $port;
							// port identification
							if ($info["port"] == 22) { $info["name"] = "SSH"; }
							elseif ($info["port"] == 25) { $info["name"] = "SMTP"; }
							elseif ($info["port"] == 80) { $info["name"] = "HTTP"; }
							elseif ($info["port"] == 3306) { $info["name"] = "MYSQL"; }
							elseif ($info["port"] == 3784) { $info["name"] = "Ventrilo"; }
							elseif ($info["port"] == 7900) { $info["name"] = "Vexis"; }
							elseif ($info["port"] == 25565) { $info["name"] = "Minecraft"; }
							else { $info["name"] = "Unknown"; }
							// port check
							$connection = @fsockopen($host, $port);
							if (is_resource($connection)) {
								$info["alert"] = "alert-success";
								$info["icon"] = "glyphicon-ok-sign";
								$info["sr-only"] = "Success:";
								$info["status"] = "online";
								fclose($connection);
							} else {
								$info["alert"] = "alert-danger";
								$info["icon"] = "glyphicon-exclamation-sign";
								$info["sr-only"] = "Error:";
								$info["status"] = "offline";
							}
							// display information
							echo "
							<p>
								<div class=\"alert {$info["alert"]}\" role=\"alert\">
									<span class=\"glyphicon {$info["icon"]}\" aria-hidden=\"true\"></span>
									<span class=\"sr-only\">{$info["sr-only"]}</span>
									{$info["name"]} service is currently {$info["status"]}.
								</div>
							</p>";
						}
					?>
				</div>
				<div class="tab-pane" id="tab_b">
					<h4>Job Queue</h4>
					<?php
						$rss = new DOMDocument();
						$rss->load('https://manager.linode.com/events/rss/f994713165e4bab1b289d39657ccf9b597968ff8');
						$feed = array();
						foreach ($rss->getElementsByTagName('item') as $node) {
							$item = array ( 
								'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
								'desc' => $node->getElementsByTagName('description')->item(0)->nodeValue,
								'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
								'date' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue,
							);
							array_push($feed, $item);
						}
						$limit = 5;
						for($x=0;$x<$limit;$x++) {
							$title = str_replace(' & ', ' &amp; ', $feed[$x]['title']);
							$link = $feed[$x]['link'];
							$description = $feed[$x]['desc'];
							$date = date('l F d, Y', strtotime($feed[$x]['date']));
							echo "
							<p>
								<strong><a href=\"{$link}\" title=\"{$title}\">{$title}</a></strong><br>
								<small><em>Posted on {$date}</em></small>
							</p>
							<p>
								{$description}
							</p>";
						}
					?>
				</div>
				<div class="tab-pane" id="tab_c">
					<h4>Minecraft Server Information</h4>
					<?php
						// minecraft status function
						function mc_status($host,$port='25565') {
							// record start time
							$timeInit = microtime();
							// clear variables
							$response = '';
							// connect
							$fp = fsockopen($host,$port,$errno,$errstr,$timeout=10);
							if(!$fp) {
								// record end time
								$timeEnd = microtime();
								// output information
								$response[0] = "Error {$errno}: {$errstr}.";
								$response[1] = 0;
								$response[2] = 0;
								// calculate times
								$timeDiff = $timeEnd-$timeInit;
								$response[] = $timeDiff < 0 ? 0 : $timeDiff;
							}
							else {
								// request information
								fputs($fp, "\xFE");
								// receive information
								while(!feof($fp)) $response .= fgets($fp);
								// close connection
								fclose($fp);
								// record end time
								$timeEnd = microtime();
								// remove NULL
								$response = str_replace("\x00", "", $response);
								// remove first portion of data
								$response = explode("\xFF\x16", $response);
								$response = $response[1];
								//echo(dechex(ord($response[0])));
								// separate data
								$response = explode("\xA7", $response);
								// calculate times
								$timeDiff = $timeEnd-$timeInit;
								$response[] = $timeDiff < 0 ? 0 : $timeDiff;
							}
							return $response;
						}
						// run status function
						$data = mc_status('localhost');
						// round request time
						$data[3] = round($data[3], 6);
						// display output
						echo "
						<p>
							MOTD: {$data[0]}<br>
							Online Players: {$data[1]}<br>
							Total Slots: {$data[2]}<br>
							Latency: {$data[3]} ms
						</p>";
					?>
				</div>
				<div class="tab-pane" id="tab_d">
					<h4>Minecraft Crash Report</h4>
					<?php
						$path = "/mnt/xvdc/services/forge/crash-reports"; 
						$latest_ctime = 0;
						$latest_filename = '';
						$d = dir($path);
						while (false !== ($entry = $d->read())) {
							$filepath = "{$path}/{$entry}";
							// could do also other checks than just checking whether the entry is a file
							if (is_file($filepath) && filectime($filepath) > $latest_ctime) {
								$latest_ctime = filectime($filepath);
								$latest_filename = $entry;
							}
						}
						$log = file_get_contents("{$path}/{$latest_filename}", NULL, NULL);
						if ($log == null) { $log = "There aren't any recent crash logs to pull from."; }
						echo "<p>".nl2br($log)."</p>";
					?>
				</div>
			</div>
		</div>
		<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
		<!-- Include all compiled plugins (below), or include individual files as needed -->
		<script src="js/bootstrap.min.js"></script>
	</body>
</html>
