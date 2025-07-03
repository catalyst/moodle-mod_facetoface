@mod @mod_facetoface
Feature: Custom field visibility can be controlled in sessions
  In order to control what session information users can see
  As a teacher
  I need to be able to set custom field visibility

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student@example.com  |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | weeks  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activity" exists:
      | activity | facetoface |
      | course   | C1         |
      | name     | Session 1  |
      | display  | 1          |
    # Create custom fields with different visibility levels
    And the following "mod_facetoface > customfields" exist:
      | name     | shortname |
      | Location | location  |

  @javascript
  Scenario: Check custom field visibility for different users
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Face-to-Face" in site administration
    And I set the field "Visible field on course page" to "Location"
    And I press "Save changes"
    And the following "mod_facetoface > sessions" exist:
      | facetoface | timestart                   | timefinish                           | customfield_location |
      | Session 1  | ##first day of next month## | ##first day of next month + 1 hour## | Room 100             |
    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Room 100"
