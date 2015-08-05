<?php
// This file is part of the customcert module for Moodle - http://moodle.org/
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

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/customcert/includes/colourpicker.php');

MoodleQuickForm::registerElementType('customcert_colourpicker',
    $CFG->dirroot . '/mod/customcert/includes/colourpicker.php', 'MoodleQuickForm_customcert_colourpicker');

/**
 * The form for handling the layout of the customcert instance.
 *
 * @package    mod_customcert
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_customcert_edit_form extends moodleform {

    /**
     * The instance id.
     */
    private $id = null;

    /**
     * The total number of pages for this cert.
     */
    private $numpages = 1;

    /**
     * Form definition.
     */
    public function definition() {
        global $DB;

        $this->id = $this->_customdata['customcertid'];

        $mform =& $this->_form;

        // Get the number of pages for this module.
        if ($pages = $DB->get_records('customcert_pages', array('customcertid' => $this->id), 'pagenumber')) {
            $this->numpages = count($pages);
            foreach ($pages as $p) {
                $this->add_customcert_page_elements($p);
            }
        }

        $mform->closeHeaderBefore('addcertpage');

        $mform->addElement('submit', 'addcertpage', get_string('addcertpage', 'customcert'));

        $mform->closeHeaderBefore('submitbtn');

        // Add the submit buttons.
        $group = array();
        $group[] = $mform->createElement('submit', 'submitbtn', get_string('savechanges'));
        $group[] = $mform->createElement('submit', 'previewbtn', get_string('savechangespreview', 'customcert'));
        $mform->addElement('group', 'submitbtngroup', '', $group, '', false);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->id);
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->setDefault('cmid', $this->_customdata['cmid']);
    }

    /**
     * Fill in the current page data for this customcert.
     */
    public function definition_after_data() {
        global $DB;

        $mform = $this->_form;

        // Check that we are updating a current customcert.
        if ($this->id) {
            // Get the pages for this customcert.
            if ($pages = $DB->get_records('customcert_pages', array('customcertid' => $this->id))) {
                // Loop through the pages.
                foreach ($pages as $p) {
                    // Set the width.
                    $element = $mform->getElement('pagewidth_' . $p->id);
                    $element->setValue($p->width);
                    // Set the height.
                    $element = $mform->getElement('pageheight_' . $p->id);
                    $element->setValue($p->height);
                    // Set the margin.
                    $element = $mform->getElement('pagemargin_' . $p->id);
                    $element->setValue($p->margin);
                }
            }
        }
    }

    /**
     * Some basic validation.
     *
     * @param $data
     * @param $files
     * @return array the errors that were found
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Go through the data and check any width, height or margin  values.
        foreach ($data as $key => $value) {
            if (strpos($key, 'pagewidth_') !== false) {
                $page = str_replace('pagewidth_', '', $key);
                $widthid = 'pagewidth_' . $page;
                // Validate that the width is a valid value.
                if ((!isset($data[$widthid])) || (!is_numeric($data[$widthid])) || ($data[$widthid] <= 0)) {
                    $errors[$widthid] = get_string('invalidwidth', 'customcert');
                }
            }
            if (strpos($key, 'pageheight_') !== false) {
                $page = str_replace('pageheight_', '', $key);
                $heightid = 'pageheight_' . $page;
                // Validate that the height is a valid value.
                if ((!isset($data[$heightid])) || (!is_numeric($data[$heightid])) || ($data[$heightid] <= 0)) {
                    $errors[$heightid] = get_string('invalidheight', 'customcert');
                }
            }
            if (strpos($key, 'pagemargin_') !== false) {
                // Validate that the margin is a valid value.
                if (isset($data[$key]) && ($data[$key] < 0)) {
                    $errors[$key] = get_string('invalidmargin', 'customcert');
                }
            }
        }

        return $errors;
    }

    /**
     * Adds the page elements to the form.
     *
     * @param stdClass $page the customcert page
     */
    private function add_customcert_page_elements($page) {
        global $DB, $OUTPUT;

        // Create the form object.
        $mform =& $this->_form;

        $mform->addElement('header', 'page_' . $page->id, get_string('page', 'customcert', $page->pagenumber));

        // Place the ordering arrows.
        // Only display the move up arrow if it is not the first.
        if ($page->pagenumber > 1) {
            $url = new moodle_url('/mod/customcert/edit.php', array('cmid' => $this->_customdata['cmid'], 'moveup' => $page->id));
            $mform->addElement('html', $OUTPUT->action_icon($url, new pix_icon('t/up', get_string('moveup'))));
        }
        // Only display the move down arrow if it is not the last.
        if ($page->pagenumber < $this->numpages) {
            $url = new moodle_url('/mod/customcert/edit.php', array('cmid' => $this->_customdata['cmid'], 'movedown' => $page->id));
            $mform->addElement('html', $OUTPUT->action_icon($url, new pix_icon('t/down', get_string('movedown'))));
        }

        $mform->addElement('text', 'pagewidth_' . $page->id, get_string('width', 'customcert'));
        $mform->setType('pagewidth_' . $page->id, PARAM_INT);
        $mform->setDefault('pagewidth_' . $page->id, '210');
        $mform->addRule('pagewidth_' . $page->id, null, 'required', null, 'client');
        $mform->addHelpButton('pagewidth_' . $page->id, 'width', 'customcert');

        $mform->addElement('text', 'pageheight_' . $page->id, get_string('height', 'customcert'));
        $mform->setType('pageheight_' . $page->id, PARAM_INT);
        $mform->setDefault('pageheight_' . $page->id, '297');
        $mform->addRule('pageheight_' . $page->id, null, 'required', null, 'client');
        $mform->addHelpButton('pageheight_' . $page->id, 'height', 'customcert');

        $mform->addElement('text', 'pagemargin_' . $page->id, get_string('margin', 'customcert'));
        $mform->setType('pagemargin_' . $page->id, PARAM_INT);
        $mform->addHelpButton('pagemargin_' . $page->id, 'margin', 'customcert');

        $mform->addElement('submit', 'downloadgrid_' . $page->id, get_string('downloadgrid', 'customcert'));

        $group = array();
        $group[] = $mform->createElement('select', 'element_' . $page->id, '', customcert_get_elements());
        $group[] = $mform->createElement('submit', 'addelement_' . $page->id, get_string('addelement', 'customcert'));
        $mform->addElement('group', 'elementgroup', '', $group, '', false);

        // Check if there are elements to add.
        if ($elements = $DB->get_records('customcert_elements', array('pageid' => $page->id), 'sequence ASC')) {
            // Get the total number of elements.
            $numelements = count($elements);
            // Create a table to display these elements.
            $table = new html_table();
            $table->head  = array(get_string('name', 'customcert'), get_string('type', 'customcert'), '');
            $table->align = array('left', 'left', 'center');
            // If we have more than one element then we can change the order, so add extra column for the up and down arrow.
            if ($numelements > 1) {
                $table->head[] = '';
                $table->align[] = 'center';
            }
            // Loop through and add the elements to the table.
            foreach ($elements as $element) {
                $row = new html_table_row();
                $row->cells[] = $element->name;
                $row->cells[] = $element->element;
                // Link to edit this element.
                $editlink = new moodle_url('/mod/customcert/edit_element.php', array('id' => $element->id,
                    'cmid' => $this->_customdata['cmid'],
                    'action' => 'edit'));
                $icons = $OUTPUT->action_icon($editlink, new pix_icon('t/edit', get_string('edit')));
                // Link to delete the element.
                $deletelink = new moodle_url('/mod/customcert/edit.php', array('cmid' => $this->_customdata['cmid'], 'deleteelement' => $element->id));
                $icons .= $OUTPUT->action_icon($deletelink, new pix_icon('t/delete', get_string('delete', 'customcert')));
                // Now display any moving arrows if they are needed.
                if ($numelements > 1) {
                    // Only display the move up arrow if it is not the first.
                    $moveicons = '';
                    if ($element->sequence > 1) {
                        $url = new moodle_url('/mod/customcert/edit.php', array('cmid' => $this->_customdata['cmid'], 'emoveup' => $element->id));
                        $moveicons .= $OUTPUT->action_icon($url, new pix_icon('t/up', get_string('moveup')));
                    }
                    // Only display the move down arrow if it is not the last.
                    if ($element->sequence < $numelements) {
                        $url = new moodle_url('/mod/customcert/edit.php', array('cmid' => $this->_customdata['cmid'], 'emovedown' => $element->id));
                        $moveicons .= $OUTPUT->action_icon($url, new pix_icon('t/down', get_string('movedown')));
                    }
                    $icons .= $moveicons;
                }
                $row->cells[] = $icons;
                $table->data[] = $row;
            }
            // Create link to order the elements.
            $link = html_writer::link(new moodle_url('/mod/customcert/rearrange.php', array('id' => $page->id)),
                    get_string('rearrangeelements', 'customcert'));
            // Add the table to the form.
            $mform->addElement('static', 'elements_' . $page->id, get_string('elements', 'customcert'), html_writer::table($table)
                    . html_writer::tag( 'div', $link, array('style' => 'text-align:right')));
            $mform->addHelpButton('elements_' . $page->id, 'elements', 'customcert');
        }

        // Add option to delete this page if there is more than one page.
        if ($this->numpages > 1) {
            // Link to delete the element.
            $deletelink = new moodle_url('/mod/customcert/edit.php', array('cmid' => $this->_customdata['cmid'], 'deletepage' => $page->id));
            $deletelink = html_writer::tag('a', get_string('deletecertpage', 'customcert'), array('href' => $deletelink->out(false), 'class' => 'deletebutton'));
            $mform->addElement('html', html_writer::tag('div', $deletelink, array('class' => 'deletebutton')));
        }
    }
}
