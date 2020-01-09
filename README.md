# MySmartLock
A small IoT project for a subject at University. 

This project is just a current non-functioning template (due to nonexistent web domain, was online for the actual demonstration) for a basic web smartlock to be implemented with a Raspberry Pi. The lock itself is simulated via a servo motor and it uses a picamera as basic means of safety for incorrect opening attempts (pictures are sent to the administrative mail access).

It is comprised by:
* A very simple web platform + database that does the password check. You can set temporary accounts and manage the lock online, too.
* A Python GUI application with a QR code to scan via a smartphone and attempt open the lock. It also provides an emergency access keyboard in case there's an Internet outage.
* A basic Android app to open the web application directly without typing the web address in a browser.

This project has been presented and defended by Javier Jiménez Sicardo (myself) and Eduardo Mayoral Briz during January/February 2019.
