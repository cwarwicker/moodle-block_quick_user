<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * User class for quick_user
 * @package    block_quick_user
 * @copyright  2019 Conn Warwicker <conn@cmrwarwicker.com>
 * @link       https://github.com/cwarwicker/moodle-block_quick_user
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace block_quick_user;

defined('MOODLE_INTERNAL') || die();

class user {

    protected $id, $firstname, $lastname, $username, $idnumber, $lastaccess;

    public function __construct($id) {

        global $DB;

        $record = $DB->get_record('user', array('id' => $id));
        if ($record) {
            $this->id = $record->id;
            $this->firstname = $record->firstname;
            $this->lastname = $record->lastname;
            $this->username = $record->username;
            $this->idnumber = $record->idnumber;
            $this->lastaccess = $record->lastaccess;
        }

    }

    /**
     * Make sure a user with this id exists
     * @return bool
     */
    public function exists() {
        return ($this->id > 0);
    }

    /**
     * Get property of object
     * @param  string $prop
     * @return mixed
     */
    public function get($prop) {

        if (property_exists($this, $prop)) {
            return $this->$prop;
        } else {
            return null;
        }

    }

    /**
     * Get the user's full name.
     * @return string
     */
    public function fullname() {

        $obj = $this->get_record();
        return ($obj) ? fullname($obj) : '';

    }

    /**
     * Get the full DB record for this user, as some functions require it to be passed in.
     * @return stdClass
     */
    public function get_record() {
        global $DB;
        return $DB->get_record('user', array('id' => $this->id));
    }

    public static function info($id) {

        global $CFG, $PAGE, $OUTPUT, $USER;

        $user = new user($id);
        if (!$user->exists()) {
            return null;
        }

        $context = $PAGE->context;
        $course = \get_course($context->instanceid);

        $renderer = $PAGE->get_renderer('block_quick_course');

        $links = array();

        // Login as the user.
        if (has_capability('moodle/user:loginas', $context)
        && $user->get('id') <> $USER->id
        && !is_siteadmin($user->get('id'))) {

            $links[] = array(
                'url' => new \moodle_url('/course/loginas.php', array(
                    'id' => $course->id,
                    'user' => $user->get('id'),
                    'sesskey' => sesskey()
                )),
                'title' => get_string('loginas') . ' ' . $user->fullname(),
                'img' => $OUTPUT->image_url('t/lock'),
                'target' => '_self'
            );

        }

        // Message user.
        $links[] = array(
            'url' => new \moodle_url('/message/index.php', array('id' => $user->get('id'))),
            'title' => get_string('sendmessage', 'block_quick_user') . ' ' . $user->fullname(),
            'img' => $OUTPUT->image_url('t/messages'),
            'target' => '_blank'
        );

        // Is block_elbp installed? If so, display a link to their ELBP page.
        // ELBP permissions are weird and we can't just do a simple capability check, so just display
        // the link and let the view.php page handle whether or not they can actually see it.
        if (!is_null( \core_plugin_manager::instance()->get_plugin_info('block_elbp') )) {

            $links[] = array(
                'url' => new \moodle_url('/blocks/elbp/view.php', array('id' => $user->get('id'))),
                'title' => get_string('viewelbp', 'block_elbp') . ' ' . $user->fullname(),
                'img' => $OUTPUT->image_url('t/user'),
                'target' => '_blank'
            );

        }

        $userobj = $user->get_record();
        $lastaccess = \format_time(time() - $user->get('lastaccess'));

        $title = $user->fullname();

        if (has_capability('moodle/user:editprofile', \context_user::instance($user->get('id')))) {
            $title .= " ({$user->get('username')})";
        }

        $title .= ' ' . get_string('lastaccess', 'block_quick_user') . ": " .
                        $lastaccess . " " . get_string('ago', 'block_quick_user');

        $output = '';
        $output .= $renderer->render_from_template('block_quick_user/user_info', array(
            'config' => $CFG,
            'user' => array(
                'id' => $user->get('id'),
                'name' => $user->fullname(),
                'picture' => $OUTPUT->user_picture($userobj, array("courseid" => $course->id, "size" => 20))
            ),
            'title' => $title,
            'links' => $links
        ));

        return $output;

    }

}