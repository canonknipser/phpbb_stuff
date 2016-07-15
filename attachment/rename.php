<?php
/**
* This script will copy all attachments from the attachment folder (default
* "files") to a separate directory, replacing the cryptic phpBB filename with the
* original filename, prepended by numeric attach_id to prevent duplicate filenames.
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);

// Name of script - change if you use a different name for the script
$scriptname = 'rename.php';
// Specify the number of attachments to copy in one run - reduce if you receive a timeout from server
$interval = 100;
// Specify the path for the copies - must have write access (CHMOD 777)
$copypath = 'filebackup';

// read id of last attachement copied
if (isset($config['last_attach_id']))
{
   $last_attach_id = $config['last_attach_id'];
}
else
{
   $last_attach_id = 0;
   set_config('last_attach_id', 0);
}

// count number of attachments to process
$sql = 'SELECT COUNT(attach_id) AS num_attach
    FROM ' . ATTACHMENTS_TABLE . '
WHERE attach_id > ' . (int) $last_attach_id . '
ORDER BY attach_id ASC';
$result = $db->sql_query($sql);
$attachs_count = (int) $db->sql_fetchfield('num_attach');

// Output Information
echo dheader();

// read required information from attachment table
$sql = 'SELECT attach_id, physical_filename, real_filename
    FROM ' . ATTACHMENTS_TABLE . '
WHERE attach_id > ' . (int) $last_attach_id . '
ORDER BY attach_id ASC';
$result = $db->sql_query_limit($sql, $interval);

// how many attachment do we copy in this run?
$actual_num = $db->sql_affectedrows($result);
if ($actual_num == 0)
{
    // nothing to do
    $complete = true;
}
else
{
    $complete = false;
    if ($attachs_count <= $interval)
    {
        // this is the last run
        $complete = true;
    }
    while ($row = $db->sql_fetchrow($result))
    {
        // for each attachment
        //remember id
        $last_attach_id = $row['attach_id'];
        // build source filename including path (we fetch the path from config
        $source = $phpbb_root_path . $config['upload_path'] . '/' . $row['physical_filename'];
        // build destination filename including path
        $destination = $phpbb_root_path . $copypath . '/' . $last_attach_id . "_" . $row['real_filename'];
        // copy the file
        if (copy ($source, $destination))
        {
            // write info to user
            echo sprintf("<tr><td class='succ'>copy succesful:</td><td>%s</td><td>%s</td><td>%s</td></tr>", $last_attach_id, $source, $destination);
        }
        else
        {
            echo sprintf("<tr><td class='error'>copy failed:</span></td><td>%s</td><td>%s</td><td>%s</td></tr>", $last_attach_id, $source, $destination);
        }
    }
    // write last attachment id in config
    set_config('last_attach_id', $last_attach_id);
}
// finished
echo dfooter();

function dheader()
{
    global $attachs_count, $interval;
    if ($interval > $attachs_count)
    {
        $interval = $attachs_count;
    }
    $remain = $attachs_count - $interval;

    return '<html>
    <head>
        <title>copy ' . $interval . ' attachments in this run, ' . $remain . ' remain to copy</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf8">
        <style>
            a:visited {COLOR: #3A4273; TEXT-DECORATION: none}
            a:link {COLOR: #3A4273; TEXT-DECORATION: none}
            a:hover {COLOR: #3A4273; TEXT-DECORATION: underline}
            .error {COLOR: red; ; FONT-WEIGHT: bold}
            .succ {COLOR: green; ; FONT-WEIGHT: bold}
            body, table, td {COLOR: #3A4273; FONT-FAMILY: Tahoma, Verdana, Arial; FONT-SIZE: 12px; LINE-HEIGHT: 20px; scrollbar-base-color: #E3E3EA; scrollbar-arrow-color: #5C5C8D}
            input {COLOR: #085878; FONT-FAMILY: Tahoma, Verdana, Arial; FONT-SIZE: 12px; background-color: #3A4273; color: #FFFFFF; scrollbar-base-color: #E3E3EA; scrollbar-arrow-color: #5C5C8D}
            .install {FONT-FAMILY: Arial, Verdana; FONT-SIZE: 20px; FONT-WEIGHT: bold; COLOR: #000000}
        </style>
    </head>
    <body bgcolor="#3A4273\" text="#000000">
        <table width="95%" border="0" cellspacing="0" cellpadding="0" bgcolor="#FFFFFF" align="center">
            <tr>
                <td>
                    <table width="98%" border="0" cellspacing="0" cellpadding="0" align="center">
                        <tr><th colspan="4">copy ' . $interval . ' attachments in this run, ' . $remain . ' remain to copy</th></tr>
                        <tr>
                            <th>Status</th><th>Attach_ID</th><th>phpBB internal file name</th><th>Copy with original file name</th>
                        </tr>';
}

function dfooter()
{
    global $scriptname, $complete;
    if (!$complete)
    {
        $next_step_link = '<a href="' . $scriptname . '">Click to continue with next step</a>';
    }
    else
    {
        $next_step_link = "<b>Completed</b>";
    }

    return '<tr><td colspan="4" align="center">' . $next_step_link . '</td></tr>
                    </table>
                </td>
            </tr>
        </table><br>
    </body>
</html>';
}

?>
