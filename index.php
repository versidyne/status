<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Versidyne Service Status</title>
		<link rel="stylesheet" href="https://cdn.rawgit.com/twbs/bootstrap/v4-dev/dist/css/bootstrap.css" crossorigin="anonymous">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
        <script src="//github.hubspot.com/tether/dist/js/tether.js"></script>
        <script src="//code.jquery.com/jquery-2.2.1.min.js"></script>
		<script src="//cdn.rawgit.com/twbs/bootstrap/v4-dev/dist/js/bootstrap.js" crossorigin="anonymous"></script>
	</head>
	<body style="padding-top:25px">
		<div class="container">
            <!-- Nav tabs -->
            <ul class="nav nav-pills nav-stacked col-md-3" id="infoTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="service-status-tab" data-toggle="tab" href="#service-status" role="tab" aria-controls="service-status" aria-expanded="true">Service Status</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="job-queue-tab" href="#job-queue" role="tab" data-toggle="tab" aria-controls="job-queue">Job Queue</a>
                </li>
				<li class="nav-item">
                    <a class="nav-link" id="minecraft-info-tab" href="#minecraft-info" role="tab" data-toggle="tab" aria-controls="minecraft-info">Minecraft Server Information</a>
				</li>
				<li class="nav-item">
                    <a class="nav-link" id="minecraft-crash-tab" href="#minecraft-crash" role="tab" data-toggle="tab" aria-controls="minecraft-crash">Minecraft Crash Report</a>
				</li>
            </ul>
            <!-- Tab panes -->
			<div class="tab-content col-md-9" id="infoTabContent">
				<div class="tab-pane fade in active" id="service-status" role="tabpanel" aria-labelledby="service-status-tab">
					<h4>Service Status</h4>
					<?php
						$services = [
                            [
                                'host' => 'localhost',
                                'port' => 22,
                                'name' => 'SSH'
                            ],
                            [
                                'host' => 'localhost',
                                'port' => 25,
                                'name' => 'SMTP',
                                'enabled' => false
                            ],
                            [
                                'host' => 'localhost',
                                'port' => 80,
                                'name' => 'HTTP'
                            ],
                            [
                                'host' => 'localhost',
                                'port' => 443,
                                'name' => 'HTTPS',
                                'enabled' => false
                            ],
                            [
                                'host' => 'localhost',
                                'port' => 3306,
                                'name' => 'MySQL'
                            ],
                            [
                                'host' => 'localhost',
                                'port' => 3784,
                                'name' => 'Ventrilo',
                                'enabled' => false
                            ],
                            [
                                'host' => 'localhost',
                                'port' => 7900,
                                'name' => 'Vexis',
                                'enabled' => false
                            ],
                            [
                                'host' => 'localhost',
                                'port' => 25565,
                                'name' => 'Minecraft',
								'enabled' => false
                            ],
                            [
                                'host' => 'udp://localhost',
                                'port' => 34197,
                                'name' => 'Factorio',
								'enabled' => false
                            ]
                        ];
						foreach ($services as $service) {
                            if (isset($service['enabled']) && !$service['enabled']) continue;
							$connection = @fsockopen($service['host'], $service['port']);
							if (is_resource($connection)) {
								$service["alert"] = "alert-success";
								$service["icon"] = "check-circle";
								$service["sr-only"] = "Success:";
								$service["status"] = "online";
								fclose($connection);
							} else {
								$service["alert"] = "alert-danger";
								$service["icon"] = "exclamation-triangle";
								$service["sr-only"] = "Error:";
								$service["status"] = "offline";
							}
							echo "
							<p>
								<div class=\"alert {$service["alert"]}\" role=\"alert\">
									<span class=\"fa fa-{$service["icon"]}\" aria-hidden=\"true\"></span>
									<span class=\"sr-only\">{$service["sr-only"]}</span>
									{$service["name"]} service is currently {$service["status"]}.
								</div>
							</p>";
						}
					?>
				</div>
				<div class="tab-pane fade" id="job-queue" role="tabpanel" aria-labelledby="job-queue-tab">
					<h4>Job Queue</h4>
					<?php
						$rss = new DOMDocument();
						$rss->load('https://manager.linode.com/events/rss/f994713165e4bab1b289d39657ccf9b597968ff8');
						$feed = array();
						foreach ($rss->getElementsByTagName('item') as $node) {
							$item = [
								'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
								'desc' => $node->getElementsByTagName('description')->item(0)->nodeValue,
								'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
								'date' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue,
                            ];
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
				<div class="tab-pane fade" id="minecraft-info" role="tabpanel" aria-labelledby="minecraft-info-tab">
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
				<div class="tab-pane fade" id="minecraft-crash" role="tabpanel" aria-labelledby="minecraft-crash-tab">
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
	</body>
</html>
