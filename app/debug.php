<?php

const DEBUG = True;

function raiseErrorAndExit(string $error_name, string $error_message) {
	if (DEBUG) {
		echo "\n\n" . '<div style="font-size: 1.5em">';
		echo "\n" . '<br>' . "\n";
		echo $error_name . ':';
		echo "\n" . '<br>' . "\n";
		echo '<pre style="white-space: pre-wrap">' . "\n";
		echo $error_message;
		echo '</pre>';
		echo "\n" . '<br>' . "\n";
		echo 'Stack trace:';
		echo "\n";
		echo '<pre style="white-space: pre-wrap">' . "\n";
		debug_print_backtrace();
		echo '</pre>';
		echo "\n" . '</div>';
	} else {
		echo 'Unexpected error occurred.';
	}
	exit();
}

function getVariableName($variable, $debug_functions = ['pprint']) {
	if (!DEBUG) {
		return;
	}
	$debug_functions[] = __FUNCTION__;
	$backtrace   = debug_backtrace();
	$i = 0;
	$backtrace_length = count($backtrace);
	while ($i < $backtrace_length &&
			in_array($backtrace[$i]['function'], $debug_functions)) {
		$i++;
	}
	$i--;
	$argument_index = array_search($variable, $backtrace[$i]['args'], False);
	if ($argument_index !== False) {
		$function_name = $backtrace[$i]['function'];
		$pattern = '/^(.*)'. $function_name . '?\(?(.*)?\)(.*)$/';
		$file = file($backtrace[$i]['file']);
		$line = $file[$backtrace[$i]['line'] - 1];
		$arguments_string_ws = preg_replace($pattern, '$2', $line);
		$arguments_string = str_replace([' ', "\t", "\n"], '', $arguments_string_ws);
		$arguments = explode(',', $arguments_string);
		$argument_name = $arguments[$argument_index];
		return $argument_name;
	}
}

function pprint($value, $options = []) {
	if (DEBUG) {
		$color_style_string = '';
		if (array_key_exists('color', $options)) {
			$color_style_string = '; color: ' . $options['color'];
		}
		echo "\n" . '<pre style="white-space: pre-wrap' .
			 $color_style_string . '">' . "\n";
		if (in_array('name', $options)) {
			echo getVariableName($value) . ":\n";
		}
		if (is_bool($value)) {
			$options[] = 'json';
		}
		if (in_array('json', $options)) {
			echo json_encode($value);
		} else {
			print_r($value);
		}
		echo "\n" . '</pre>' . "\n";
	}
}

?>