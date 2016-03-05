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
						$host = 'localhost';
						$ports = array(22, 25, 80, 3306, 7900, 25565);
						foreach ($ports as $port) {
							$info = array();
							$info["port"] = $port;
							if ($info["port"] == 22) { $info["name"] = "SSH"; }
							elseif ($info["port"] == 25) { $info["name"] = "SMTP"; }
							elseif ($info["port"] == 80) { $info["name"] = "HTTP"; }
							elseif ($info["port"] == 3306) { $info["name"] = "MYSQL"; }
							elseif ($info["port"] == 3784) { $info["name"] = "Ventrilo"; }
							elseif ($info["port"] == 7900) { $info["name"] = "Vexis"; }
							elseif ($info["port"] == 25565) { $info["name"] = "Minecraft"; }
							else { $info["name"] = "Unknown"; }
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
					<p>
						<?php
							function mc_status($host,$port='25565') {
								$timeInit = microtime();
								$fp = fsockopen($host,$port,$errno,$errstr,$timeout=10);
								if(!$fp) {
									$timeDiff = microtime()-$timeInit;
									$response = [];
									$response[0] = ($timeDiff > 0) ? $timeDiff : 0;
									$response[1] = "Error {$errno}: {$errstr}.";
									$response[2] = 0;
									$response[3] = 0;
									$response[4] = 0;
								} else {
									$data = '';
									fputs($fp, "\xFE");
									while(!feof($fp)) $data = fgets($fp);
									fclose($fp);
									$timeEnd = microtime();
									$timeDiff = $timeEnd-$timeInit;

									if (!empty($data)) {
										$data = str_replace("\x00", "", $data);
										$raw = chunk_split(strtoupper(bin2hex($data)), 2, " ");
										$data = str_replace("\xFF", "", $data);
										$version = hexdec(bin2hex(substr($data, 0, 1)));
										$data = explode("\xA7", substr($data, 1));
										$data[] = $version;
									} else {
										$data = [0, 0, 0];
									}
									$diagnostics = [
										($timeDiff > 0) ? $timeDiff : 0,
										$raw
									];
									$response = array_merge($diagnostics, $data);
								}
								return $response;
							}
							//$data = mc_status('72.192.190.11');
							$data = mc_status('localhost');
							$data[0] = round($data[0], 6);
							if ($data[5] > 5 && $data[5] < 47) {
								$data[5] = "1.7.10";
							} elseif ($data[5] == 47) {
								$data[5] = "1.8";
							} elseif ($data[5] > 47 && $data[5] < 74) {
								$data[5] = "1.8.8";
							} else {
								$data[5] = "Unknown Protocol {$data[5]}";
							}
							if (isset($data[2])) echo "MOTD: {$data[2]}<br>";
							if (isset($data[3])) echo "Online Players: {$data[3]}<br>";
							if (isset($data[4])) echo "Total Slots: {$data[4]}<br>";
							if (isset($data[5])) echo "Version: {$data[5]}<br>";
							echo "Latency: {$data[0]} ms<br>";
							echo "Raw (Hex): {$data[1]}";
						?>
					</p>
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
