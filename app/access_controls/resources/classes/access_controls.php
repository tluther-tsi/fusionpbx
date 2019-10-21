<?php

/**
 * access controls class
 *
 * @method null download
 */
if (!class_exists('access_controls')) {
	class access_controls {

		/**
		 * declare private variables
		 */
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;
		private $enabled_prefix;

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'access_controls';
				$this->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
				$this->permission_prefix = 'access_control';
				$this->list_page = 'access_controls.php';
				$this->table = 'access_controls';
				$this->uuid_prefix = 'access_control_';
				$this->enabled_prefix = 'access_control_';

		}

		/**
		 * called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * delete records
		 */
		public function delete($records) {
			if (permission_exists($this->permission_prefix.'_delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//build the delete array
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									$array['access_control_nodes'][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		/**
		 * copy records
		 */
		public function copy($records) {
			if (permission_exists($this->permission_prefix.'_add')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//copy the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get checked records
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$record_uuids[] = $this->uuid_prefix."uuid = '".$record['uuid']."'";
								}
							}

						//create insert array from existing data
							if (is_array($record_uuids) && @sizeof($record_uuids) != 0) {
								$sql = "select * from v_".$this->table." ";
								$sql .= "where ".implode(' or ', $record_uuids)." ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									$y = 0;
									foreach ($rows as $x => $row) {
										//primary table
											$primary_uuid = uuid();
											$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $primary_uuid;
											$array[$this->table][$x]['access_control_name'] = $row['access_control_name'];
											$array[$this->table][$x]['access_control_default'] = $row['access_control_default'];
											$array[$this->table][$x]['access_control_description'] = trim($row['access_control_description'].' ('.$text['label-copy'].')');
										//sub table
											$sql_2 = "select * from v_access_control_nodes where access_control_uuid = :access_control_uuid";
											$parameters_2['access_control_uuid'] = $row['access_control_uuid'];
											$database = new database;
											$rows_2 = $database->select($sql_2, $parameters_2, 'all');
											if (is_array($rows_2) && @sizeof($rows_2) != 0) {
												foreach ($rows_2 as $row_2) {
													$array['access_control_nodes'][$y]['access_control_node_uuid'] = uuid();
													$array['access_control_nodes'][$y]['access_control_uuid'] = $primary_uuid;
													$array['access_control_nodes'][$y]['node_type'] = $row_2['node_type'];
													$array['access_control_nodes'][$y]['node_cidr'] = $row_2['node_cidr'];
													$array['access_control_nodes'][$y]['node_domain'] = $row_2['node_domain'];
													$array['access_control_nodes'][$y]['node_description'] = $row_2['node_description'];
													$y++;
												}
											}
											unset($sql_2, $parameters_2, $rows_2, $row_2);
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//set message
									message::add($text['message-copy']);

							}
							unset($records);
					}

			}
		}

	}
}

?>