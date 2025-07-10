@mod @mod_facetoface
Feature: A session can be hidden to specific users
  In order to control which sessions students can see
  As a teacher
  I need to be able to hide and show sessions

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

  @javascript
  Scenario: Create a visible session for students
    Given the following "mod_facetoface > sessions" exist:
      | facetoface | timestart                   | timefinish                           | visible |
      | Session 1  | ##first day of next month## | ##first day of next month + 1 hour## | 1       |
    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Sign-up"
    And I follow "View all sessions"
    Then I should see "Sign-up"

  Scenario: Create a hidden session for students
    Given the following "mod_facetoface > sessions" exist:
      | facetoface | timestart                   | timefinish                           | visible |
      | Session 1  | ##first day of next month## | ##first day of next month + 1 hour## | 0       |
    When I am on the "C1" "Course" page logged in as "student1"
    Then I should not see "Sign-up"
    And I follow "View all sessions"
    Then I should see "No upcoming sessions"

  Scenario: Ensure teachers can still see hidden sessions
    Given the following "mod_facetoface > sessions" exist:
      | facetoface | timestart                   | timefinish                           | visible |
      | Session 1  | ##first day of next month## | ##first day of next month + 1 hour## | 0       |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "Sign-up"
    And I follow "View all sessions"
    Then I should see "Sign-up"
