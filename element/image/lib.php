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

require_once($CFG->dirroot . '/mod/customcert/element/element.class.php');

/**
 * The customcert element image's core interaction API.
 *
 * @package    customcertelement_image
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class customcert_element_image extends customcert_element_base {

    /**
     * This function renders the form elements when adding a customcert element.
     *
     * @param mod_customcert_edit_element_form $mform the edit_form instance
     */
    public function render_form_elements($mform) {
        global $COURSE;

        $mform->addElement('select', 'image', get_string('image', 'customcertelement_image'), self::get_images());

        $mform->addElement('text', 'width', get_string('width', 'customcertelement_image'), array('size' => 10));
        $mform->setType('width', PARAM_INT);
        $mform->setDefault('width', 0);
        $mform->addHelpButton('width', 'width', 'customcertelement_image');

        $mform->addElement('text', 'height', get_string('height', 'customcertelement_image'), array('size' => 10));
        $mform->setType('height', PARAM_INT);
        $mform->setDefault('height', 0);
        $mform->addHelpButton('height', 'height', 'customcertelement_image');

        $mform->addElement('text', 'posx', get_string('posx', 'customcert'), array('size' => 10));
        $mform->setType('posx', PARAM_INT);
        $mform->setDefault('posx', '0');
        $mform->addHelpButton('posx', 'posx', 'customcert');

        $mform->addElement('text', 'posy', get_string('posy', 'customcert'), array('size' => 10));
        $mform->setType('posy', PARAM_INT);
        $mform->setDefault('posy', '0');
        $mform->addHelpButton('posy', 'posy', 'customcert');

        $filemanageroptions = array('maxbytes' => $COURSE->maxbytes,
            'subdirs' => 1,
            'accepted_types' => 'image');

        $mform->addElement('filemanager', 'customcertimage', get_string('uploadimage', 'customcert'), '', $filemanageroptions);
    }

    /**
     * Performs validation on the element values.
     *
     * @param array $data the submitted data
     * @param array $files the submitted files
     * @return array the validation errors
     */
    public function validate_form_elements($data, $files) {
        // Array to return the errors.
        $errors = array();

        // Check if width is not set, or not numeric or less than 0.
        if ((!isset($data['width'])) || (!is_numeric($data['width'])) || ($data['width'] < 0)) {
            $errors['width'] = get_string('invalidwidth', 'customcertelement_image');
        }

        // Check if height is not set, or not numeric or less than 0.
        if ((!isset($data['height'])) || (!is_numeric($data['height'])) || ($data['height'] < 0)) {
            $errors['height'] = get_string('invalidheight', 'customcertelement_image');
        }

        // Validate the position.
        $errors += $this->validate_form_element_position($data);

        return $errors;
    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param stdClass $data the form data
     */
    public function save_form_elements($data) {
        global $COURSE;

        // Handle file uploads.
        customcert_upload_imagefiles($data->customcertimage, context_course::instance($COURSE->id)->id);

        parent::save_form_elements($data);
    }

    /**
     * This will handle how form data will be saved into the data column in the
     * customcert_elements table.
     *
     * @param stdClass $data the form data
     * @return string the json encoded array
     */
    public function save_unique_data($data) {
        // Array of data we will be storing in the database.
        $arrtostore = array(
            'pathnamehash' => $data->image,
            'width' => $data->width,
            'height' => $data->height
        );

        return json_encode($arrtostore);
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     */
    public function render($pdf, $preview) {
        global $CFG;

        // If there is no element data, we have nothing to display.
        if (empty($this->element->data)) {
            return;
        }

        $imageinfo = json_decode($this->element->data);

        // Get the image.
        $fs = get_file_storage();
        if ($file = $fs->get_file_by_hash($imageinfo->pathnamehash)) {
            $contenthash = $file->get_contenthash();
            $l1 = $contenthash[0] . $contenthash[1];
            $l2 = $contenthash[2] . $contenthash[3];
            $location = $CFG->dataroot . '/filedir' . '/' . $l1 . '/' . $l2 . '/' . $contenthash;
            $pdf->Image($location, $this->element->posx, $this->element->posy, $imageinfo->width, $imageinfo->height);
        }
    }

    /**
     * Sets the data on the form when editing an element.
     *
     * @param mod_customcert_edit_element_form $mform the edit_form instance
     */
    public function definition_after_data($mform) {
        global $COURSE;

        // Set the image, width and height for this element.
        $imageinfo = json_decode($this->element->data);
        $this->element->image = $imageinfo->pathnamehash;
        $this->element->width = $imageinfo->width;
        $this->element->height = $imageinfo->height;

        // Editing existing instance - copy existing files into draft area.
        $draftitemid = file_get_submitted_draft_itemid('customcertimage');
        $filemanageroptions = array('maxbytes' => $COURSE->maxbytes,
            'subdirs' => 1,
            'accepted_types' => 'image');
        file_prepare_draft_area($draftitemid, context_course::instance($COURSE->id)->id, 'mod_customcert', 'image', 0,
            $filemanageroptions);
        $element = $mform->getElement('customcertimage');
        $element->setValue($draftitemid);

        parent::definition_after_data($mform);
    }

    /**
     * Return the list of possible images to use.
     *
     * @return array the list of images that can be used
     */
    public static function get_images() {
        global $COURSE;

        // Create file storage object.
        $fs = get_file_storage();

        // The array used to store the images.
        $arrfiles = array();
        // Loop through the files uploaded in the system context.
        if ($files = $fs->get_area_files(context_system::instance()->id, 'mod_customcert', 'image', false, 'filename', false)) {
            foreach ($files as $hash => $file) {
                $arrfiles[$hash] = $file->get_filename();
            }
        }
        // Loop through the files uploaded in the course context.
        if ($files = $fs->get_area_files(context_course::instance($COURSE->id)->id, 'mod_customcert', 'image', false, 'filename', false)) {
            foreach ($files as $hash => $file) {
                $arrfiles[$hash] = $file->get_filename();
            }
        }

        core_collator::asort($arrfiles);
        $arrfiles = array_merge(array('0' => get_string('noimage', 'customcert')), $arrfiles);

        return $arrfiles;
    }
}
