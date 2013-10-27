<?php

// This file keeps track of upgrades to
// the data module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

function xmldb_data_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();


    // Moodle v2.2.0 release upgrade line
    // Put any upgrade step following this

    // Moodle v2.3.0 release upgrade line
    // Put any upgrade step following this

    if ($oldversion < 2012112901) {
        // Check if there is a directory containing any old presets.
        $olddatadir = $CFG->dataroot . '/data';
        $oldpresetdir = "$olddatadir/preset";
        if (file_exists($oldpresetdir)) {
            // Get directory contents.
            $userfolders = new DirectoryIterator($oldpresetdir);
            // Store the system context, these are site wide presets.
            $context = get_system_context();
            // Create file storage object.
            $fs = get_file_storage();
            // Create array of accepted files.
            $arracceptedfilenames = array('singletemplate.html', 'listtemplateheader.html', 'listtemplate.html',
                                          'listtemplatefooter.html', 'addtemplate.html', 'rsstemplate.html',
                                          'rsstitletemplate.html', 'csstemplate.css', 'jstemplate.js',
                                          'asearchtemplate.html', 'preset.xml');
            // Loop through all the folders, they should represent userids.
            foreach ($userfolders as $userfolder) {
                // If it is a file, skip it.
                if ($userfolder->isFile()) {
                    continue;
                }
                // The folder name should represent the user id.
                $userid = $userfolder->getFilename();
                // Skip if it is not numeric.
                if (!is_numeric($userid)) {
                    continue;
                }
                // Skip if the number does not correspond to a user (does not matter if user was deleted).
                if (!$DB->record_exists('user', array('id' => $userid))) {
                    continue;
                }
                // Open this folder.
                $presetfolders = new DirectoryIterator("$oldpresetdir/$userid");
                foreach ($presetfolders as $presetfolder) {
                    // If it is a file, skip it.
                    if ($presetfolder->isFile()) {
                        continue;
                    }
                    // Save the name of the preset.
                    $presetname = $presetfolder->getFilename();
                    // Get the files in this preset folder.
                    $presetfiles = new DirectoryIterator("$oldpresetdir/$userid/$presetname");
                    // Now we want to get the contents of the presets.
                    foreach ($presetfiles as $file) {
                        // If it is not a file, skip it.
                        if (!$file->isFile()) {
                            continue;
                        }
                        // Set the filename.
                        $filename = $file->getFilename();
                        // If it is not in the array of accepted file names skip it.
                        if (!in_array($filename, $arracceptedfilenames)) {
                            continue;
                        }
                        // Store the full file path.
                        $fullfilepath = "$oldpresetdir/$userid/$presetname/$filename";
                        // Create file record.
                        $filerecord = array('contextid' => $context->id,
                                            'component' => 'mod_data',
                                            'filearea' => 'site_presets',
                                            'itemid' => 0,
                                            'filename' => $filename,
                                            'userid' => $userid);
                        // Check to ensure it does not already exists in the file directory.
                        if (!$fs->file_exists($context->id, 'mod_data', 'site_presets', 0, '/' . $presetfolder . '/', $filename)) {
                            $filerecord['filepath'] = '/' . $presetfolder . '/';
                        } else {
                            $filerecord['filepath'] = '/' . $presetfolder . '_' . $userid . '_old/';
                        }
                        $fs->create_file_from_pathname($filerecord, $fullfilepath);
                        // Remove the file.
                        @unlink($fullfilepath);
                    }
                    // Remove the preset directory.
                    @rmdir("$oldpresetdir/$userid/$presetname");
                }
                // Remove the user directory.
                @rmdir("$oldpresetdir/$userid");
            }
            // Remove the final directories.
            @rmdir("$oldpresetdir");
            @rmdir("$olddatadir");
        }

        upgrade_mod_savepoint(true, 2012112901, 'data');
    }

    // Moodle v2.4.0 release upgrade line
    // Put any upgrade step following this


    // Moodle v2.5.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2013071500) {

        // Extend structure of data
        $table = new xmldb_table('data');

        // Define field workflowenable to be added to data
        $field = new xmldb_field('workflowenable', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'notification');
        // Conditionally launch add field workflowenable
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field workflowid to be added to data
        $field = new xmldb_field('workflowid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'workflowenable');
        // Conditionally launch add field workflowid
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        // Extend structure of data_records
        $table = new xmldb_table('data_records');

        // Define field wfstateid to be added to data_records
        $field = new xmldb_field('wfstateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'approved');
        // Conditionally launch add field wfstateid
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key dataid (foreign) to be added to data_records
        $key = new xmldb_key('dataid', XMLDB_KEY_FOREIGN, array('dataid'), 'data', array('id'));
        // Launch add key dataid
        $dbman->add_key($table, $key);


        // Define table data_wf to be created
        $table = new xmldb_table('data_wf');

        // Adding fields to table data_wf
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('wfname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('initstateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table data_wf
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for data_wf
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }


        // Define table data_wf_states to be created
        $table = new xmldb_table('data_wf_states');

        // Adding fields to table data_wf_states
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('wfid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('statename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('statedescr', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table data_wf_states
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('wfid', XMLDB_KEY_FOREIGN, array('wfid'), 'data_wf', array('id'));

        // Conditionally launch create table for data_wf_states
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }


        // Define table data_wf_actions to be created
        $table = new xmldb_table('data_wf_actions');

        // Adding fields to table data_wf_actions
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('wfid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fromstateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tostateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('actname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('actdescr', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table data_wf_actions
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('wfid', XMLDB_KEY_FOREIGN, array('wfid'), 'data_wf', array('id'));
        $table->add_key('fromstateid', XMLDB_KEY_FOREIGN, array('fromstateid'), 'data_wf_states', array('id'));
        $table->add_key('tostateid', XMLDB_KEY_FOREIGN, array('tostateid'), 'data_wf_states', array('id'));

        // Conditionally launch create table for data_wf_actions
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }


        // Define table data_wf_state_role_allow to be created
        $table = new xmldb_table('data_wf_state_role_allow');

        // Adding fields to table data_wf_state_role_allow
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('wfid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('allowstateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table data_wf_state_role_allow
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('wfid', XMLDB_KEY_FOREIGN, array('wfid'), 'data_wf', array('id'));
        $table->add_key('allowstateid', XMLDB_KEY_FOREIGN, array('allowstateid'), 'data_wf_states', array('id'));

        // Adding indexes to table data_wf_state_role_allow
        $table->add_index('wfstateroleallow', XMLDB_INDEX_NOTUNIQUE, array('wfid', 'allowstateid'));

        // Conditionally launch create table for data_wf_state_role_allow
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // data savepoint reached
        upgrade_mod_savepoint(true, 2013071500, 'data');
    }

    if ($oldversion < 2013071600) {

        // Extend structure of data_wf_states
        $table = new xmldb_table('data_wf_states');

        // Define field notification to be added to data_wf_states
        $field = new xmldb_field('notification', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1', 'statedescr');

        // Conditionally launch add field notification
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // data savepoint reached
        upgrade_mod_savepoint(true, 2013071600, 'data');
    }

    return true;
}
