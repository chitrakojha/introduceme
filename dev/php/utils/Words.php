<?php

class Words {

	/**
	 * Converts an array into a properly formatted piece of prose
	 * @param array $array
	 * @param string $and The localised word for 'and'
	 */
	public static function arrayToList($array, $and) {
		$prose = '';
		for ($i=0; $i<count($array); $i++) {
			$prose .= $array[$i];
			if ($i < count($array) - 2) {
				// Add a comma
				$prose .= ', ';
			} elseif ($i < count($array) - 1) {
				// Add an 'and' or 'or'
				$prose .= " $and ";
			}
		}
		return $prose;
	}

}

