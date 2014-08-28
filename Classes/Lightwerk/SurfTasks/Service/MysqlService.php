<?php
namespace Lightwerk\SurfTasks\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Mysql Service
 *
 * @Flow\Scope("singleton")
 * @package Lightwerk\SurfTasks
 */
class MysqlService {

	/**
	 * @param array $options
	 * @return string
	 */
	public function getMysqlArguments($options) {
		$arguments = array();
		$argumentKeys = array('username', 'password', 'host', 'socket', 'port');

		foreach ($argumentKeys as $key) {
			if (empty($options[$key])) {
				continue;
			}
			$value = escapeshellarg($options[$key]);
			if (strlen($key) === 1) {
				$arguments[$key] = '-' . $key . ' ' . $value;
			} else {
				$arguments[$key] = '--' . $key . '=' . $value;
			}
		}

		if (!empty($options['database'])) {
			$arguments[] = $options['database'];
		}
		return implode(' ', $arguments);
	}
}