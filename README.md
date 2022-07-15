# ACAC_Marquee
Project to display swim meet and other facility information on LED Marquee signs donated by General Electric. 

This code is run from a Raspberry Pi inside the sign which is connected by ethernet to a local network. A computer on the local network can open the webage of the sign (IP address) to set text to display from the sign.

At startup, the raspberry Pi will command the display to show the IP address.

This code consists of two main portions, python and web.

The Python script was written with Python 2.7, so may need some updating to support 3+.

The website consists mainly of php and JavaScript. The JavaScript code was not developed as part of this project, instead it was used in its entirety from a different source.

In order to communicate with a marquee sign, the Raspberry Pi uses a USB->serial adapter (even though a GPIO pin on the Raspberry Pi could have been used directly).

To make the system seamless, the python script needs to be started automatically when the device starts up.
