<?php defined('SYSPATH') or die('No direct access allowed.');

// Define the possible configuration file locations
$filenames = array(
    // The linux configuration file location
    DIRECTORY_SEPARATOR.
        'etc'.DIRECTORY_SEPARATOR.
        'mm_library'.DIRECTORY_SEPARATOR.
        'couchdb.ini',
    // The windows configuration file location
    'C:'.DIRECTORY_SEPARATOR.
        'ProgramData'.DIRECTORY_SEPARATOR.
        'MongooseMetrics'.DIRECTORY_SEPARATOR.
        'mm_Library'.DIRECTORY_SEPARATOR.
        'couchdb.ini'
);

// Make sure that at least one of these two files is defined
foreach ($filenames as $index => $filename) {
    // If the file exists, break out of the loop
    if (file_exists($filename)) {
        // Break out of the loop
        break;
    }

    // If this is the last attempt through the loop
    if ($index === count($filenames) - 1) {
        // Throw an exception
        throw new Kohana_Exception('Missing required ":filename" '.
            'configuration file.', array(':filename' => 'couchdb.ini'));
    }
}

// Define the required variable names to search for in the config file
$required_variables = array('COUCHDB_HOSTNAME', 'COUCHDB_DATABASE_NAME');

// Loop over the list of possible configuration file locations
foreach ($filenames as $filename) {
    // If the current filename does not exist on the disk
    if ( ! file_exists($filename)) {
        // Move on to the next file
        continue;
    }

    // Attempt to parse the file as an INI file
    $data = parse_ini_file($filename, TRUE);

    // Convert all of the array keys to lowercase
    $data = array_change_key_case($data);

    // Loop over the list of required variables
    foreach ($required_variables as $key) {
        // Convert the key to both upper and lowercase versions
        $uppercase_key = strtoupper($key);
        $lowercase_key = strtolower($key);

        // If this key does not exist in the config file
        if ( ! isset($data[$lowercase_key])) {
            // Throw an exception
            throw new Kohana_Exception('Missing required option ":key" in '.
                '":filename" configuration file.', array(
                    ':key' => $key,
                    ':filename' => $filename
                ));
        }

        // Add this value to the finished environment array
        $environment[$uppercase_key] = $data[$lowercase_key];
    }

    // Break out of this loop
    break;
}

// Loop over the variables that we need to get
foreach (array_keys($environment) as $variable_name) {
    // If the apache_getenv function does not exist and no $_SERVER variable
    // exists
    if (! function_exists('apache_getenv') AND
        ! isset($_SERVER[$variable_name])) {
        // Move on to the next variable
        continue;
    }

    // Attempt to get the Apache environment variable by this name
    $variable_value = function_exists('apache_getenv') ?
        apache_getenv($variable_name) : $_SERVER[$variable_name];

    // If we did not get a value
    if ( ! is_string($variable_value)) {
        // Move on to the next one
        continue;
    }

    // Overwrite the default value with the environment variable value
    $environment[$variable_name] = $variable_value;
}

// Erase all of the temporary variables we created
unset($filenames, $index, $filename, $required_variables, $data, $key,
    $variable_name, $variable_value);

return array(
	'default' => array(
		/**
		 * host:     the location and protocol of the couchdb server
		 * database: the name of the database in the couchdb server
		 */
		'host'     => $environment['COUCHDB_HOSTNAME'],
		'database' => $environment['COUCHDB_DATABASE_NAME']
	)
);
