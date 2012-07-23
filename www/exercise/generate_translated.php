<?php

$path = __DIR__ . '/exercises';

foreach (scandir($path) as $name) {
	$file = "$path/$name";
	if (is_file($file)) {
		$template = file_get_contents($file);

		$transfile = __DIR__ . "/czech/" . substr($name, 0, -5) . ".txt";
		if (!file_exists($transfile)) {
			continue;
		}

		$translation = file_get_contents($transfile);
		$matches = [];
		preg_match_all('~(?P<tid>\d+\.\d+)\s+(?P<text>.*?)\n$~ims', $translation, $matches);

		/*
		$template = preg_replace_callback('~
			(<([^ >]+)[^>]*?)
			\s+data-tid="(?P<tid>\d+\.\d+)"\s*
			(/?>)(?P<inner>.*?)(</(\2))
		~imsx',
		*/
		$template = preg_replace_callback('~
			(?P<prepend>
				<(p|title)
				.*?
			)
			\s+data-tid="(?P<tid>\d+\.\d+)"\s*
			(?P<prepend2>
				/?>)(?P<inner>.*?)(?P<append></(\2)>)
		~imsx',
			function ($match) use ($matches) {
				foreach ($matches['tid'] as $i => $tid) {
					if ($tid == $match['tid']) {
						break;
					}
				}
				
				return ($match['prepend'] . $match['prepend2'] . $matches['text'][$i] . $match['append']);
			}, $template);
		file_put_contents(__DIR__ . "/translated/$name", $template);
	}
}