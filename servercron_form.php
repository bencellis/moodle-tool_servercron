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
 * Server Cron
 *
 * Plugin to manage the http cron jobs for moodle
 *
 * @package    tool_servercron
 * @copyright  2012 Nottingham University
 * @author     Benjamin Ellis <benjamin.c.ellis@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
require_once("$CFG->libdir/formslib.php");

/**
 * servercron_form - form to manage cron jobs
 *
 * This class defines the form for the admin interface
 * @copyright  2012 Nottingham University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class servercron_form extends moodleform {

    /**
     * Function defines the form to be displayed via Moodle forms functionality
     */
    public function definition() {
        global $CFG, $PAGE;

        $mform =& $this->_form;

        //edit section
        $mform->addElement('header', 'configheader', get_string('exitingcrontitle', 'tool_servercron'));

        $existing = $this->_customdata['existingrecs'];
        $rows = '';

        if (count($existing)) {
            //set up heading for the table
            $row = html_writer::tag('th', get_string('minuteprompt', 'tool_servercron'),
                array('width' => '5%', 'style' => 'padding:5px; text-align:right'));

            $row .= html_writer::tag('th', get_string('hourprompt', 'tool_servercron'),
                array('width' => '5%', 'style' => 'padding:5px; text-align:right'));

            $row .= html_writer::tag('th', get_string('dayprompt', 'tool_servercron'),
                array('width' => '5%', 'style' => 'padding:5px; text-align:right'));

            $row .= html_writer::tag('th', get_string('monthprompt', 'tool_servercron'),
                array('width' => '5%', 'style' => 'padding:5px; text-align:right'));

            $row .= html_writer::tag('th', get_string('wdayprompt', 'tool_servercron'),
                array('width' => '5%', 'style' => 'padding:5px; text-align:right'));

            $row .= html_writer::tag('th', get_string('commandprompt', 'tool_servercron'),
                array('width' => '30%', 'style' => 'padding:5px; text-align:center'));

            $row .= html_writer::tag('th', get_string('actionsprompt', 'tool_servercron'),
                array('style' => 'padding:5px; text-align:center'));

            $row = html_writer::tag('tr', $row, array('width' => '100%'));
            $rows .= $row ."\n";

            foreach ($existing as $exists) {
                // make up the edit line
                $row = html_writer::tag('td', $exists->minute,  array('style' => 'text-align: right'));
                $row .= html_writer::tag('td', $exists->hour,  array('style' => 'text-align: right'));
                $row .= html_writer::tag('td', $exists->day,  array('style' => 'text-align: right'));
                $row .= html_writer::tag('td', $exists->month,  array('style' => 'text-align: right'));
                $row .= html_writer::tag('td', $exists->wday,  array('style' => 'text-align: right'));
                $row .= html_writer::tag('td', $exists->commandline);

                //editing links
                $row .= html_writer::start_tag('td', array('style' => 'padding:5px; text-align:center'));

                $row .= html_writer::tag('a', '['.get_string('editcronjob', 'tool_servercron').']',
                    array('id' => 'svrcrn'.$exists->id,
                        'href' => $PAGE->url."?action=edit&cronjobid=".$exists->id));

                $row .= '&nbsp;&nbsp;';

                $row .= html_writer::tag('a', '['.get_string('deletecronjob', 'tool_servercron').']',
                    array('id' => 'svrcrn'.$exists->id, 'href' =>  $PAGE->url."?action=delete&cronjobid=".$exists->id));

                $row .= html_writer::end_tag('td');

                $row = html_writer::tag('tr', $row);
                $rows .= $row ."\n";
            }

            $mform->addElement('html', html_writer::tag('table', $rows, array('width' => '100%')));          //enclose in table
        } else {
            //if no rec id specified - then we have no records
            if (!$this->_customdata['cronjobid']) {
                $mform->addElement('html', html_writer::tag('p', get_string('noexistingcrons', 'tool_servercron')));
            }
        }

        $editing = false;           //deafult not editing existing record
        if ($this->_customdata['cronjobid'] != 0) {
            $editing = true;
        }
        //new section
        if ($editing) {
            $mform->addElement('header', 'configheader', get_string('editcronstitle', 'tool_servercron') .' [' .
                $this->_customdata['cronjobid'] . ']' );
        } else {
            $mform->addElement('header', 'configheader', get_string('newcronstitle', 'tool_servercron'));
        }

        if (isset($this->_customdata['error'])) {
            $mform->addElement('html', '<h3 style="color: red">'.$this->_customdata['error'].'</h3>');
        }

        //hidden field
        $mform->addElement('hidden', 'cronjobid', $this->_customdata['cronjobid'], array('id' => 'id_cronjobid'));//0=new
        // default action is save - have to check for cancel in php code to avoid reliance on JS
        $mform->addElement('hidden', 'action', 'save', array('id' => 'id_action'));

        $timingdets=array();

        $select = $mform->createElement('select', 'minute', get_string('minuteprompt', 'tool_servercron'),
            $this->_customdata['minutes']);
        $select->setMultiple(true);
        $timingdets[] = $select;

        $select = $mform->createElement('select', 'hour', get_string('hourprompt', 'tool_servercron'),
            $this->_customdata['hours']);
        $select->setMultiple(true);
        $timingdets[] = $select;

        $select = $mform->createElement('select', 'day', get_string('dayprompt', 'tool_servercron'),
            $this->_customdata['days']);
        $select->setMultiple(true);
        $timingdets[] = $select;

        $select = $mform->createElement('select', 'month', get_string('monthprompt', 'tool_servercron'),
            $this->_customdata['months']);
        $select->setMultiple(true);
        $timingdets[] = $select;

        $select = $mform->createElement('select', 'wday', get_string('wdayprompt', 'tool_servercron'),
            $this->_customdata['wdays']);
        $select->setMultiple(true);
        $timingdets[] = $select;

        //set the defaults for all the dropdowns as every *
        if (isset($this->_customdata['minute'])) {
            $mform->setDefault('minute', $this->_customdata['minute']);
        } else {
            $mform->setDefault('minute', -1);
        }

        if (isset($this->_customdata['hour'])) {
            $mform->setDefault('hour', $this->_customdata['hour']);
        } else {
            $mform->setDefault('hour', -1);
        }

        if (isset($this->_customdata['day'])) {
            $mform->setDefault('day', $this->_customdata['day']);
        } else {
            $mform->setDefault('day', -1);
        }

        if (isset($this->_customdata['month'])) {
            $mform->setDefault('month', $this->_customdata['month']);
        } else {
            $mform->setDefault('month', -1);
        }

        if (isset($this->_customdata['wday'])) {
            $mform->setDefault('wday', $this->_customdata['wday']);
        } else {
            $mform->setDefault('wday', -1);
        }

        //now add the group to the form
        $mform->addGroup($timingdets, 'timings', get_string('timingsprompt', 'tool_servercron'), array(' '), false);

        //servercron title
        $mform->addElement('text', 'commandline', get_string('commandprompt', 'tool_servercron'), array('size' => 100));
        $mform->setDefault('commandline', $this->_customdata['commandline']);
        $mform->setType('commandline', PARAM_TEXT);

        //buttons
        $buttonarray=array();
        $buttonarray[] = $mform->createElement('submit', 'save', get_string('cronjobsave', 'tool_servercron'));

        if ($editing) {
            $buttonarray[] = $mform->createElement('cancel', 'cancel', get_string('croneditcancel', 'tool_servercron'));
        }

        $buttonarray[] = $mform->createElement('reset', 'resetbutton', get_string('cronjobreset', 'tool_servercron'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

    }
}

/* ?> */