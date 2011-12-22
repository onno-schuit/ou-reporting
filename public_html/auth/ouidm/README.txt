README.TXT for Moodle OU-IDM Auth plugin

Author: Menno de Ridder (m.deridder@solin.nl)
Copyrights: Stoas Intermedia
Version: 2011122201

The OU-IDM Auth Plugin is created to synchronize the IDM database with
the Moodle database upon login. It creates, edits and deletes users,
groups and course enrolments.


1) Installation

To start using the OU-IDM Auth plugin please follow the follow steps.
- Copy and paste all the folders in the ouidm folder to the correct 
  position in the moodle codebase.
- Go to the admin/settings.php?section=manageauths. Select the newly
  added plugin to be used.


2) Synchronization

The KR Code of the User links this user to the ID Number of the course.
This is how the system will know which course the user is supposed
to be enrolled in. Below are the different possible scenario's that
can occure when a user logs in.

* A user does NOT exist in IDM, but does exist moodle
  This user will be ignored, no actions will be taken.

* A user does exist in IDM, but does NOT exist in moodle
  This user will be created in the moodle database, and
  synchronized according to the data available in IDM.

* A user exists in IDM AND in Moodle
  This user will be synchronized according to the data
  available in IDM.


2.1) Manual synchronization

To prevent this synchronization a course can be created with the course id
set as "manual". In this case the courses will be ignored in the
synchronization process.


3) Unenroling users

To unenroll users without forcing them to log in before they are unenrolled
the cron job "cron_ouidm_auth" (cron_ouidm_auth.php) has been created. Run 
this cron job and the pending users for unenrolment will be processed.



** PREREQUISITES / ASSUMPTIONS **
Because of the option to manually controll the users and groups in a course
it is not allowed to use the keyword "manual" in a course ID-number UNLESS it
actually has to act manually.