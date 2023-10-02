@mod @mod_facetoface
Feature: Teacher may set up facetoface completion based on attendance

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student@example.com  |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion | showcompletionconditions |
      | Course 1 | C1        | 1                | 1                        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: Require full attendance for facetoface completion
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I click on "Add an activity or resource" "button" in the "General" "section"
    And I click on "Add a new Face-to-Face" "link"
    And I expand all fieldsets

    And I set the following fields to these values:
      | Name                | Test seminar                                      |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require view        | 0                                                 |
      | Require attendance  | Full attendance is required                       |
    And I press "Save and display"
    And I should see "Full attendance is required" in the ".automatic-completion-conditions" "css_element"
    And I follow "Add a new session"
    And I set the following fields to these values:
      | Session date/time known | 1                 |
      | timestart[0][day]       | 1                 |
      | timestart[0][month]     | January           |
      | timestart[0][year]      | 2023              |
      | timestart[0][hour]      | 01                |
      | timestart[0][minute]    | 01                |
      | timefinish[0][day]      | 2                 |
      | timefinish[0][month]    | January           |
      | timefinish[0][year]     | 2023              |
      | timefinish[0][hour]     | 01                |
      | timefinish[0][minute]   | 01                |
    And I press "Save changes"
    And I follow "Attendees"
    And I follow "Add/remove attendees"
    And I set the field "addselect" to "Student 1"
    And I click on "Add" "button"
    And I follow "Go back"
    And I log out

    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "To do: Full attendance is required"
    And I log out

    When I am on the "C1" "Course" page logged in as "teacher1"
    And I follow "View all sessions"
    And I follow "Attendees"
    And I follow "Take attendance"
    And I set the field with xpath "//*[contains(concat(' ', normalize-space(@class), ' '), ' menusubmissionid_')]" to "No show"
    And I press "Save attendance"
    And I log out
    And I am on the "C1" "Course" page logged in as "student1"
    Then I should see "To do: Full attendance is required"
    And I log out

    When I am on the "C1" "Course" page logged in as "teacher1"
    And I follow "View all sessions"
    And I follow "Attendees"
    And I follow "Take attendance"
    And I set the field with xpath "//*[contains(concat(' ', normalize-space(@class), ' '), ' menusubmissionid_')]" to "Partially attended"
    And I press "Save attendance"
    And I log out
    And I am on the "C1" "Course" page logged in as "student1"
    Then I should see "To do: Full attendance is required"
    And I log out

    When I am on the "C1" "Course" page logged in as "teacher1"
    And I follow "View all sessions"
    And I follow "Attendees"
    And I follow "Take attendance"
    And I set the field with xpath "//*[contains(concat(' ', normalize-space(@class), ' '), ' menusubmissionid_')]" to "Fully attended"
    And I press "Save attendance"
    And I log out
    And I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Done: Full attendance is required"
    And I log out

  @javascript
  Scenario: Require at least partial attendance for facetoface completion
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I click on "Add an activity or resource" "button" in the "General" "section"
    And I click on "Add a new Face-to-Face" "link"
    And I expand all fieldsets

    And I set the following fields to these values:
      | Name                | Test seminar                                      |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require view        | 0                                                 |
      | Require attendance  | At least partial attendance is required                       |
    And I press "Save and display"
    And I should see "At least partial attendance is required" in the ".automatic-completion-conditions" "css_element"
    And I follow "Add a new session"
    And I set the following fields to these values:
      | Session date/time known | 1                 |
      | timestart[0][day]       | 1                 |
      | timestart[0][month]     | January           |
      | timestart[0][year]      | 2023              |
      | timestart[0][hour]      | 01                |
      | timestart[0][minute]    | 01                |
      | timefinish[0][day]      | 2                 |
      | timefinish[0][month]    | January           |
      | timefinish[0][year]     | 2023              |
      | timefinish[0][hour]     | 01                |
      | timefinish[0][minute]   | 01                |
    And I press "Save changes"
    And I follow "Attendees"
    And I follow "Add/remove attendees"
    And I set the field "addselect" to "Student 1"
    And I click on "Add" "button"
    And I follow "Go back"
    And I log out

    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "To do: At least partial attendance is required"
    And I log out

    When I am on the "C1" "Course" page logged in as "teacher1"
    And I follow "View all sessions"
    And I follow "Attendees"
    And I follow "Take attendance"
    And I set the field with xpath "//*[contains(concat(' ', normalize-space(@class), ' '), ' menusubmissionid_')]" to "No show"
    And I press "Save attendance"
    And I log out
    And I am on the "C1" "Course" page logged in as "student1"
    Then I should see "To do: At least partial attendance is required"
    And I log out

    When I am on the "C1" "Course" page logged in as "teacher1"
    And I follow "View all sessions"
    And I follow "Attendees"
    And I follow "Take attendance"
    And I set the field with xpath "//*[contains(concat(' ', normalize-space(@class), ' '), ' menusubmissionid_')]" to "Partially attended"
    And I press "Save attendance"
    And I log out
    And I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Done: At least partial attendance is required"
    And I log out

    When I am on the "C1" "Course" page logged in as "teacher1"
    And I follow "View all sessions"
    And I follow "Attendees"
    And I follow "Take attendance"
    And I set the field with xpath "//*[contains(concat(' ', normalize-space(@class), ' '), ' menusubmissionid_')]" to "Fully attended"
    And I press "Save attendance"
    And I log out
    And I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Done: At least partial attendance is required"
    And I log out
