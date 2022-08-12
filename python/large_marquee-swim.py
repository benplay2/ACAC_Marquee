#Python script: adapted form of script:
#---------------------------------------------------------------------------
# large_marquee.py
#
# Copyright: GE Intelligent Platforms
# 2010, 2011, 2012, 2013
#
# Greg Faust
#
# 4/19/2013 0.02
#---------------------------------------------------------------------------
#
#New version (adapted with permission from Greg Faust):
#---------------------------------------------------------------------------
# large_marquee-swim.py
# Permission granted to distribute at will from Greg Faust
# Copyright: Benjamin Brust
# 2013
#
# 7/24/2013
# Version 2.35
#---------------------------------------------------------------------------

#---------------------------------------------------------------------------
#This file is designed to be used in tandem with the web page to control the
#display. It is set up to use website files in /var/www.
#
#The web page functionality can be enabled by installing apache2.
#This is done by running "sudo apt-get update"
#followed by "sudo apt-get install apache2" in the terminal.
#
#
#IN ORDER TO MAKE IT WORK:
#the /var/www folder must be accessable by the user running this code
#One way to do it is to set the folder permissions to 777 with chmod.
#   Another way would be to create and set the permissions of the
#   text, php, and html files manually.
#
#A USB -> serial port is required for operation. While our tests have
#always used the top USB port of the Pi, it may not matter which port.
#This serial port must be connected to the marquee's RS232 recieve
#and ground ports.
#The USB-> serial converter used in the past has had a DB9 connector,
#which sends from pin 3, and ground is pin 5.
#
#Files required:
#ALL TEXT FILES WILL BE AUTOMATICALLY GENERATED
#
#All text files must be writable by all. (Taken care of by first_run())
#
#Text files used:
#badwords-big-opt.txt -> In directory of this file
#/var/www/theme.txt -> contains boolean & message
#/var/www/score.txt -> contains boolean & message
#/var/www/custT1.txt -> contains boolean & message
#/var/www/custT2.txt -> contains boolean & message
#/var/www/clerkCall.txt -> contains boolean & event1 $ event2
#/var/www/clerkMsg.txt -> contains boolean & message
#/var/www/custT3.txt -> contains boolean & message
#/var/www/ID.txt -> contains device ID
#/var/www/defTheme.txt -> contains default theme
#/var/www/cenLev.txt -> contains censorship level
#/var/www/ip.txt -> stores the IP address of the running system on one line
#/var/www/status.txt -> stores the last few status messages to update the web
#var/www/time.txt -> contains boolean value for time, as well as initial set time in case of no pi internet access
#/var/www/sysMsg.txt -> stores system error messages
#/var/www/sysDo.txt -> contains one line that instructs the python code to perform special tasks with sysMsg.txt
#
#Website Files used:
#Must have PHP enabled, and set php files to executable.
#    (Executable permission is taken care of by first_run())
#/var/www/index.html -> redirects to index.php
#/var/www/index.php -> contains the user interface. Calls ajax_handler.php.
#/var/www/admin.php -> Use to change ID of display (Password is raspberry)
#       Password not very secure, just to avoid changes from wrong people
#/var/www/ajax_handler.php -> Performs work on the text files. Called by index.php.
#/var/www/jquery-1.10.0.min.js -> javascript file to handle calling php file from javascript
#
#----------------------------------------------------------------------------
#
#Recommendations:
#-Turn off ssh for security reasons
#-This is designed to work through a dedicated secured router, which is the
#   only security put in place to prevent unauthorized from abusing the powers
#-To make this system run automatically on startup:
#       Make sure apache is installed (web server)
#       Add "sudo python [directory]/large_marquee-swim.py &" to
#       /etc/rc.local just above exit 0.
#       The directory is where the python file is saved.
#       The & is to continue with boot tasks after starting.


import time
import datetime
import sys
from socket import *
import urllib
from xml.dom import minidom
import os
import serial
import signal
import commands
import stat
import urllib2
import subprocess

serverPort = 10001

netconnect = False;

ctrl_0 = '\x00' #null
ctrl_a = '\x01' #hold
ctrl_b = '\x02' #flash
ctrl_c = '\x03' #scroll
ctrl_d = '\x04' #scroll up
ctrl_e = '\x05' #scroll down
ctrl_f = '\x06' #cause characters to flash
ctrl_h = '\x08' #scroll right
ctrl_i = '\x09' #scroll left
ctrl_k = '\x0B' #wipe up
ctrl_l = '\x0C' #wipe down
ctrl_n = '\x0E' #wipe left
ctrl_o = '\x0F' #wipe right
ctrl_r = '\x12' #reset command
ctrl_s = '\x13' #clear out the auto-repeat buffer
ctrl_u = '\x15' #change color
ctrl_v = '\x16' #send over variable
ctrl_w = '\x17' #enable auto-repeat mode
ctrl_x = '\x18' #stops auto repeat mode
ctrl_y = '\x19' #add following text and mode control to repeat buffer
ESC = '\x1B'
CR = '\x0D'
space = " "

# display modes for commands
hold = '\x01'
flash = '\x02'
scroll = '\x03'
scroll_up = '\x04'
scroll_down = '\x05'
scroll_right = '\x08'
scroll_left = '\x09'
wipe_up = '\x0B'
wipe_down = '\x0C'
wipe_left = '\x0E'
wipe_right = '\x0F'

# display modes for text fields
line_mode_1 = '0'
line_mode_2 = '1'
flash_mode = '2'
smooth_scroll_mode = '3'
scroll_up_mode = '4'
scroll_down_mode = '5'
scroll_right_mode = '6'
scroll_left_mode = '7'
wipe_up_mode = '8'
wipe_down_mode = '9'
wipe_left_mode = 'A'
wipe_right_mode = 'B'
roll_up_mode = 'C'

# commands embedded in auto repeat buffer strings

reset_str = ctrl_r
flash_string = ctrl_f
red_text = ctrl_u + 'R'
green_text = ctrl_u + 'G'
yellow_text = ctrl_u + 'Y'

# line number - line the text is displayed
# see page 28 of manual

tl1 = '1'
tl2 = '2'
tl3 = '3'
tl4 = '4'
tl5 = '5'
tl6 = '6'

maddress = '\x01'

auto_text_hdr = ctrl_y + ESC + 'T'   #auto buffer text header

interval = 3
ticks = 0
timeTicks = 0

no_delay = '00'
delay = '01'
long_delay = '03'
longer_delay = '04'
longerer_delay = '05'

TRUE = 1
FALSE = 0

var_0 = ESC + 'V' + '0'
var_1 = ESC + 'V' + '1'
var_2 = ESC + 'V' + '2'
var_3 = ESC + 'V' + '3'
var_4 = ESC + 'V' + '4'
var_5 = ESC + 'V' + '5'
var_6 = ESC + 'V' + '6'
var_7 = ESC + 'V' + '7'
var_8 = ESC + 'V' + '8'
var_9 = ESC + 'V' + '9'

#variables following are to help with updating messages
timeOn = False;
timeSetWeb = False;
timeSetManual = False;
themeOn = True;
scoreOn = False;
custT1On = False;
custT2On = False;
clerkCallOn = False;
clerkMsgOn = False;
custT3On = False;

sysLines = 0;

devID = 0;

ip_address = commands.getoutput("/sbin/ifconfig").split("\n")[1].split()[1][5: ]
if ("cast" in ip_address.lower()):#If the Pi does not have an IP address
    ip_prefix = "Network "
    ip_address = "Not Connected"
else:
    ip_prefix = "Website ==> http://"




alarmTick = 0
lastChangeNum = 0; #last number in change.txt

def boolConvert(text):
    """
    Convert text true or false from web to python readable True and False (Boolean).
    """
    text = text.lower()
    if "true" in text:
        return True
    elif "false" in text:
        return False
    else:
        return text

def writeSysMsg(message):
    """
    Add system message to the sysMsg.txt
    Returns Boolean
    """
    global sysLines

    if sysLines == 0:
        setSysLines()

    if sysLines > 300:
        shortenSysMsg()
        
    complete = False
    
    filePath = "/var/www/sysMsg.txt"
    try:
        file = open(filePath, 'a+')#Open the file for appending
        file.write("\n")
        file.write(message)
        file.close()
        sysLines += 1
        complete = True
    except:
        complete = False
        print("Failed to write message to sysMsg.txt: " + str(message))
    
    if complete:
        return True
    else:
        return False

def setSysLines():
    """
    Set sysLines variable to the number of lines in sysMsg.txt
    Returns: integer
    """
    global sysLines
    filePath = "/var/www/sysMsg.txt"
    try:
        file = open(filePath, 'r')
        sysLines = len(list(file))
        file.close()
        return sysLines
    except:
        writeSysMsg("Failed to retrieve length of sysMsg.txt")
        return 0

def shortenSysMsg():
    """
    Function to shorten sysMsg.txt to a manageable length
    Return: boolean
    """
    global sysLines

    if sysLines < 35:
        return False
    sysLines = 1
    filePath = "/var/www/sysMsg.txt"
    try:
        file = open(filePath, 'r')
        msg_list = file.readlines()
        file.close()
    except:
        setSysLines()
        message = "Failed to create list from sysMsg.txt"
        print(message)
        writeSysMsg(message)
        return False
    
    try:#clear sysMsg.txt
        file = open(filePath, 'w+')
        file.write(msg_list[0].strip('\n'))
        file.close()
    except:
        setSysLines()
        message = "Failed to clear sysMsg.txt"
        print(message)
        writeSysMsg(message)
        return False

    index = -29
    while index < 0:
        writeSysMsg(msg_list[index].strip('\n'))
        index += 1
    setSysLines()
    return True
    
    
def censor(text):
    """
    This censors out any bad words found in message.
    Uses text file of unacceptable words.

    Returns an acceptable string.
    """
    try:
        cenLev_list = []
        filePath = "/var/www/cenLev.txt"
        file = open(filePath, 'rU')
        cenLev_list = file.readlines()
        file.close()
        cenLev = cenLev_list[0]
    except:
        cenLev = 3
        message = "Unable to find cenLev.txt default value. Using 3 (default)"
        writeSysMsg(message)
        print(message)
        
    try:
        badFound = False

        textL = text.lower() #make string input lowercase
        in_list = textL.split()
        
        index = 0
        for word in in_list:
            in_list[index] = word.translate(None, '!@#$%^&*()-=+~`,./;:') #remove symbols from words entered
            index += 1

        bad_list = []
        filePath = "/home/pi/marquee_py/badwords-big-opt.txt"
        #filePath = "./badwords-big-opt.txt"
        file = open(filePath, 'rU')
        bad_list = file.readlines()
        file.close()

        index = 0
        for badWord in bad_list:
            bad_list[index] = badWord.strip() #remove carriage return of badWord
            bad_list[index] = bad_list[index].translate(None, '!@#$%^&*()-=+~`,./;:') #remove any characters in the bad_list (shouldn't be any, precaution)
            index += 1

        badIndices_list = [] #indicies of the word and its match are stored in here
        badFoundCount_list = [] #the count of bad word matches are stored in here, each entry is for each word
        badFoundCount = 0
        inIndex = 0
        for word in in_list:
            badWordIndex = 0
            badFoundCount = 0
            for badWord in bad_list:
                if badWord in word:
                    badFound = True
                    badFoundCount += 1
                    pair = [[[inIndex],[badWordIndex]]]
                    badIndices_list += pair #mark index as containing bad word
                    #message = "Bad word found: " + badWord
                    print(message)
                    writeSysMsg(message)
                badWordIndex += 1
            badFoundCount_list += [badFoundCount]
            inIndex += 1
        
        if badFound == False:
            return text
        else:
            #check more in depth on the bad word(s)
            print(badIndices_list)
            confirmBadIndex_list = [] #the indexes of words to be removed are stored in here
            for markedPair in badIndices_list:
                if len(in_list[markedPair[0][0]]) == len(bad_list[markedPair[1][0]]):#if the words are the same length, its bad
                    confirmBadIndex_list += [markedPair[0][0]]
                    message = "Bad word confirmed (same length as bad), removed : " + in_list[markedPair[0][0]]
                #if the bad word is >= 5 characters long, the other word is most likely bad
                elif len(bad_list[markedPair[1][0]]) >= 5:
                    confirmBadIndex_list += [markedPair[0][0]]
                    message = "Bad word confirmed (root > 5 long), removed : " + in_list[markedPair[0][0]]
                print(message)
                writeSysMsg(message)
            index = 0
            for count in badFoundCount_list:
                if count >= int(cenLev):#if cenLev matches or more, kill it
                    confirmBadIndex_list += [index]
                    message = "Bad word confirmed (matched at least cenLev times, removed : " + in_list[index]
                    print(message)
                    writeSysMsg(message)
                index += 1
            #assemble answer, replacing bad words with no characters
            final_list = text.split()
            for badIndex in confirmBadIndex_list:
                final_list[badIndex] = '' * (len(final_list[badIndex]))
            return " ".join(final_list)
    except:
        message = "Unable to censor text - badwords-big-opt.txt not found"
        writeSysMsg(message)
        print(message)
        return text

def formatText(text):
    """
    This makes the text have correct spacing for the display

    20 characters large letters line_mode_1
    21.5 characters large letters smooth_scroll_mode
    probably 40 characters small letters
    add space to beginning
    """
    #currently all text is in large format
    """
    textLen = len(text)
    if textLen >= 250:
        textOut = text
    elif textLen <= 18: #text will be displayed in line_mode_1 after padding
        textOut = text
    else:#add a space between each message
        textOut = 1 * (' ') + text
    """
    textOut = text
    return textOut

def upd_marquee():
    """update marquee buffer
    Goes through text files, checks to see if the option is turned on,
    then displays them on the marquee.
    """
    print("Running upd_marquee()")
    global timeOn
    global timeSetWeb
    global timeSetManual
    global devID
    global timeSetWeb
    global timeSetManual
    global themeOn
    global scoreOn
    global custT1On
    global custT2On
    global clerkCallOn
    global clerkMsgOn
    global custT3On
    global ip_address
    global ip_prefix
    
    firstRun = False;
    top_list = []
    bottom_list = []

    
    
    #this file includes a number that will change with any change to website.
    #when changed, update marquee will run
    #if the first line is zero, show the default theme and web address
    change_list = []
    filePath = "/var/www/change.txt"
    try:
        file = open(filePath, 'rU')
        change_list = file.readlines()
        file.close()
    except:
        message = "Failed to open change.txt"
        writeSysMsg(message)
        print(message)
        change_list = ['0']

    #this file has one line, the default theme
    defTheme_list = []
    filePath = "/var/www/defTheme.txt"
    try:
        file = open(filePath, 'rU')
        defTheme_list = file.readlines()
        file.close()
        defThemeEdit = censor(defTheme_list[0])
    except:
        message = "Unable to find default theme file (defTheme.txt)"
        writeSysMsg(message)
        print(message)

    #this file has one line, the ID
    ID_list = []
    filePath = "/var/www/ID.txt"
    try:
        file = open(filePath, 'rU')
        ID_list = file.readlines()
        file.close()
        devID = censor(ID_list[0])
    except:
        message = "Failed to open ID.txt"
        writeSysMsg(message)
        print(message)
        devID = "No ID"
    
    if change_list[0] == "0":
        firstRun = True;
    else:
        #the following 4 blocks get text for the top half
        theme_list = []
        filePath = "/var/www/time.txt"
        try:
            file = open(filePath, 'rU')
            time_list = file.readlines()
            file.close()
        except:
            message = "Failed to open time.txt"
            time_list = ["false"]
            writeSysMsg(message)
            print(message)

        newTimeOn = boolConvert(time_list[0])
        #if there is a change, then worry about changing stuff
        if (not (timeOn == newTimeOn) and newTimeOn):
            timeOn = newTimeOn
            #check for internet ->  not in use to eliminate possible issues
            #if (not (timeSetWeb)):
                #check_network()
            if (not timeSetWeb):#if the time has not been set automatically with internet
                #need to set manually
                try:
                    date_str = time_list[1]
                    valid = True
                except:
                    valid = False
                    message = "Given time value is missing"
                    writeSysMsg(message)
                    print(message)
                    
                
                #M/D/Y H:M:S
                #then set time with given info - IF VALID
                if valid:
                    print("Setting date manually: " , date_str)
                    try:
                        #os.system('hwclock --set %s' % date_str)
                        #os.system('sudo date -s %s' % date_str)
                        #The following line sets the date as a separate process,
                        #eliminating long wait time
                        tp = subprocess.Popen(["sudo","/bin/date" , "-s" , date_str])
                    except:
                        message =  "Failed to set clock manually"
                        timeOn = False
                        valid = False
                        writeSysMsg(message)
                        print(message)
                #if not valid - check to see if set before (timeSetManual)
                if valid == False:
                    timeOn = timeSetManual
                    #if not, timeOn = false.
                    if timeOn == False:
                        #change txt file to resemble
                        filePath = "/var/www/time.txt"
                        try:
                            file = open(filePath, 'w+')
                            file.write("false")
                            file.close()
                        except:
                            message =  "Failed to change time.txt"
                            writeSysMsg(message)
                            print(message)
                else:
                    timeSetManual = True
            else:#internet is used
                print("Setting date with internet")
                today = datetime.date.today()
        else:
            timeOn = newTimeOn

        theme_list = []
        filePath = "/var/www/theme.txt"
        try:
            file = open(filePath, 'rU')
            theme_list = file.readlines()
            file.close()
        except:
            message =  "Failed to open theme.txt"
            theme_list = ["false"]
            writeSysMsg(message)
            print(message)

        score_list = []
        filePath = "/var/www/score.txt"
        try:
            file = open(filePath, 'rU')
            score_list = file.readlines()
            file.close()
        except:
            score_list = ["false"]
            message =  "Failed to open score.txt"
            writeSysMsg(message)
            print(message)

        custT1_list = []
        filePath = "/var/www/custT1.txt"
        try:
            file = open(filePath, 'rU')
            custT1_list = file.readlines()
            file.close()
        except:
            message =  "Failed to open custT1.txt"
            custT1_list = ["false"]
            writeSysMsg(message)
            print(message)
            

        custT2_list = []
        filePath = "/var/www/custT2.txt"
        try:
            file = open(filePath, 'rU')
            custT2_list = file.readlines()
            file.close()
        except:
            message = "Failed to open custT2.txt"
            custT2_list = ["false"]
            writeSysMsg(message)
            print(message)

        #the following 3 blocks get the text for the bottom half
        clerkCall_list = []
        filePath = "/var/www/clerkCall.txt"
        try:
            file = open(filePath, 'rU')
            clerkCall_list = file.readlines()
            file.close()
        except:
            message =  "Failed to open clerkCall.txt"
            clerkCall_list = ["false"]
            writeSysMsg(message)
            print(message)

        clerkMsg_list = []
        filePath = "/var/www/clerkMsg.txt"
        try:
            file = open(filePath, 'rU')
            clerkMsg_list = file.readlines()
            file.close()
        except:
            message =  "Failed to open clerkMsg.txt"
            clerkMsg_list = ["false"]
            writeSysMsg(message)
            print(message)

        custT3_list = []
        filePath = "/var/www/custT3.txt"
        try:
            file = open(filePath, 'rU')
            custT3_list = file.readlines()
            file.close()
        except:
            message =  "Failed to open custT3.txt"
            custT3_list = ["false"]
            writeSysMsg(message)
            print(message)


        #the following code assembles the list to display
        themeOn = boolConvert(theme_list[0])
        if themeOn:
            themeEdit = censor(theme_list[1])
            themeEdit = formatText(themeEdit)
            top_list += [themeEdit]
            
        scoreOn = boolConvert(score_list[0])
        if scoreOn:
            scoreEdit = censor(score_list[1])
            scoreEdit = formatText(scoreEdit)
            top_list += [scoreEdit]
            
        custT1On = boolConvert(custT1_list[0])
        if custT1On:
            custT1Edit = censor(custT1_list[1])
            custT1Edit = formatText(custT1Edit)
            top_list += [custT1Edit]
            
        custT2On = boolConvert(custT2_list[0])
        if custT2On:
            custT2Edit = censor(custT2_list[1])
            custT2Edit = formatText(custT2Edit)
            top_list += [custT2Edit]

        clerkCallOn = boolConvert(clerkCall_list[0])
        if clerkCallOn:
            clerkCallEdit1 = censor(clerkCall_list[1])

        clerkMsgOn = boolConvert(clerkMsg_list[0])
        if clerkMsgOn:
            clerkMsgEdit = censor(clerkMsg_list[1])
            clerkMsgEdit = formatText(clerkMsgEdit)
            bottom_list += [clerkMsgEdit]

        custT3On = boolConvert(custT3_list[0])
        if custT3On:
            custT3Edit = censor(custT3_list[1])
            custT3Edit = formatText(custT3Edit)
            bottom_list += [custT3Edit]

# begin sending data to the marquees

    ser = serial.Serial()
    ser.baudrate = 9600
    ser.port = '/dev/ttyUSB0'
    ser.parity = serial.PARITY_NONE
    ser.timeout = 3
    ser.xonxoff = False
    ser.rtscts = False
    ser.open()
    
    ser.write(ctrl_s + maddress + ctrl_a + CR) #clear auto-repeat buffer
    ser.write(ctrl_y + ctrl_r + maddress + '2' + CR) #clear display

    time.sleep(1.5)

    topLen = len(top_list)
    bottomLen = len(bottom_list)
        
    if not firstRun:
        #The time function
        if timeOn:
            if topLen >= 1:
            #the following line wipes previous message away
                ser.write(auto_text_hdr +
                    tl3 +
                    scroll_up_mode +
                    no_delay +
                    ' ' +
                    maddress +
                    ctrl_a +
                    CR)
                time.sleep(.5)
                
            #the following line displays the time (stored as var_1, which is td_message)
            ser.write(auto_text_hdr +
                tl3 +
                line_mode_1 +
                long_delay +
                var_1 +
                maddress +
                ctrl_a +
                CR)
            time.sleep(.5)
            #td_message = time.strftime('%a %d-%b-%y %I:%M')
            td_message = time.strftime('%a %b-%d %I:%M')
            
            ser.write(ctrl_v + '1' + td_message + maddress + ctrl_a + CR) #update time variable (set variable 1 to td_message)
            time.sleep(0.5)

        maxItems = max(topLen,bottomLen)
        index = 0
        clerkCallDone = False
        while index < maxItems:
            #this while loop will cycle through the messages, alternating between top and bottom

            #The following loop displays the next bottom message
            if bottomLen > index:
                message = bottom_list[index]
                if len(message) <= 20:
                    displayType = line_mode_1
                else:
                    displayType = smooth_scroll_mode
                    #the following line wipes previous message away
                    ser.write(auto_text_hdr +
                        tl6 +
                        scroll_down_mode +
                        no_delay +
                        ' ' +
                        maddress +
                        ctrl_a +
                        CR)
                    time.sleep(.5)
                ser.write(auto_text_hdr +
                    tl6 +
                    displayType +
                    long_delay +
                    message +
                    maddress +
                    ctrl_a +
                    CR)
                time.sleep(0.5)
            elif not(clerkCallDone) and clerkCallOn:
                #write clerk
                #the following line displays the bottom message: clerk call
                #var_2 is an event entry
                #Will be displayed as: Events __ & __ to clerk
                clerkCallStart = "Events "
                clerkCallEnd = " to Clerk"
                displayType = smooth_scroll_mode
                #the following line wipes previous message away
                ser.write(auto_text_hdr +
                    tl6 +
                    scroll_down_mode +
                    no_delay +
                    ' ' +
                    maddress +
                    ctrl_a +
                    CR)
                time.sleep(.5)
                ser.write(auto_text_hdr +
                    tl6 +
                    displayType +
                    long_delay +
                    clerkCallStart +
                    var_2 +
                    clerkCallEnd +
                    maddress +
                    ctrl_a +
                    CR)
                time.sleep(.5)
                ser.write(ctrl_v + '2' + clerkCallEdit1 + maddress + ctrl_a + CR) #update variable (set variable 2 to clerkCallEdit1)
                time.sleep(0.5)
                clerkCallDone = True
                
            #The following displays the next top message
            if topLen > index:
                message = top_list[index]
                if len(message) <= 20:
                    displayType = line_mode_1
                else:
                    displayType = smooth_scroll_mode
                    #the following line wipes previous message away
                    ser.write(auto_text_hdr +
                        tl3 +
                        scroll_up_mode +
                        no_delay +
                        ' ' +
                        maddress +
                        ctrl_a +
                        CR)
                    time.sleep(.5)
                ser.write(auto_text_hdr +
                    tl3 +
                    displayType +
                    long_delay +
                    message +
                    maddress +
                    ctrl_a +
                    CR)
                time.sleep(.5)
                
            index += 1
            

        if not(clerkCallDone) and clerkCallOn:#Clerk has not been written yet
            #the following line displays the bottom message: clerk call
            #var_2 is an event entry
            #Will be displayed as: Events __ & __ to clerk
            clerkCallStart = "Events "
            clerkCallEnd = " to Clerk"
            displayType = smooth_scroll_mode
            #the following line wipes previous message away
            ser.write(auto_text_hdr +
                tl6 +
                scroll_down_mode +
                no_delay +
                ' ' +
                maddress +
                ctrl_a +
                CR)
            time.sleep(.5)
            ser.write(auto_text_hdr +
                tl6 +
                displayType +
                long_delay +
                clerkCallStart +
                var_2 +
                clerkCallEnd +
                maddress +
                ctrl_a +
                CR)
            time.sleep(.5)
            ser.write(ctrl_v + '2' + clerkCallEdit1 + maddress + ctrl_a + CR) #update variable (set variable 2 to clerkCallEdit1)
            time.sleep(0.5)
            clerkCallDone = True

    else:#change_list[0] == "0"
        #code to display default theme and web address
        #show default theme on top line
        ser.write(auto_text_hdr +
                tl3 +
                line_mode_1 +
                delay +
                defThemeEdit +
                maddress +
                ctrl_a +
                CR)
        time.sleep(0.5)
        #show web address to connect
        ser.write(auto_text_hdr +
                tl4 +
                line_mode_1 +
                delay +
                var_6 +
                var_4 +
                maddress +
                ctrl_a +
                CR)

        #"Website ==> http://" + or "Network"
        time.sleep(0.5)
        ser.write(ctrl_v + '4' + ip_address + maddress + ctrl_a + CR) #update variable (set variable 4 to ip_address)
        time.sleep(0.5)
        ser.write(ctrl_v + '6' + ip_prefix + maddress + ctrl_a + CR) #update variable (set variable 6 to ip_address prefix)
        time.sleep(0.5)

        #show web address to connect
        ser.write(auto_text_hdr +
                tl5 +
                line_mode_1 +
                delay +
                "Unit Name ==> " +
                var_5 +
                maddress +
                ctrl_a +
                CR)
        
        ser.write(ctrl_v + '5' + devID + maddress + ctrl_a + CR) #update variable (set variable 5 to devID)
        time.sleep(0.5)
        
    ser.write(ctrl_w + '2' + maddress + ctrl_a + CR) #enables auto repeat buffer

    ser.close()

def upd_tdt():
    """
    update the time in all marquees
    
    Return: none
    """
    
    ser = serial.Serial()
    ser.baudrate = 9600
    ser.port = '/dev/ttyUSB0'
    ser.parity = serial.PARITY_NONE
    ser.timeout = 3
    ser.xonxoff = False
    ser.rtscts = False
    ser.open()
    td_message = time.strftime('%a %b-%d %I:%M')
    #td_message = time.strftime('%a %d-%b-%y %I:%M')

    ser.write(ctrl_v + '1' + td_message + maddress + ctrl_a + CR) #update time variable
    ser.close()

def check_network():
    """
    test a connection to see if network is up
    write the result to /var/www/internet.txt, only if internet is working

    returns boolean
    """
    global netconnect
    global timeSetWeb
    old_netconnect = netconnect
    try:
        urllib2.urlopen("http://google.com", timeout = 2)
        print("Internet connection found")
        timeSetWeb = True
        netconnect = True
    except:
        message = "Failed to connect to google.com"
        netconnect = False
        writeSysMsg(message)
        print(message)
        
    filePath = "/var/www/internet.txt"
    if (not(old_netconnect == netconnect)):
        # if there is a change from the past, change the file
        try:
            print("Writing internet.txt")
            internet_file = open(filePath,'w+')
            if netconnect:
                internet_file.write("true")
            else:
                internet_file.write("false")
            internet_file.close()

        except:
            message =  "Failed to write internet status"
            writeSysMsg(message)
            print(message)
            
    timeSetWeb = False
    #This is to eliminate use of internet to set clock.
    #(eliminates possible errors)

    return netconnect
    
def alarm_handler(signo, frame):
    """
    provide periodic execution of marquee updates

    Checks change.txt to see if any updates available.
    """
    global lastChangeNum
    global ip_address
    global ip_prefix
    global ticks
    global alarmTick
    global timeOn
    global devID
    global timeTicks
    global interval
    global netconnect
    global timeSetWeb
    global timeSetManual
    global themeOn
    global scoreOn
    global custT1On
    global custT2On
    global clerkCallOn
    global clerkMsgOn
    global custT3On
    
    ticks += 1
    
    alarmTick += 1
    
    if timeOn:
        tickNum = 30/interval #want to update time every 30 seconds
        timeTicks += 1
        if tickNum == timeTicks:
            upd_tdt()
            time.sleep(0.5)
            timeTicks = 0
            
    #this file includes a number that will change with any change to website.
    #when changed, update marquee will run
    #if the first line is zero, show the default theme and web address
    change_list = []
    filePath = "/var/www/change.txt"
    file = open(filePath, 'rU')
    change_list = file.readlines()
    file.close()
    
    if change_list[0]== "0" and alarmTick > 1:#must update ip_address variable, ip_prefix, and devID
        alarmTick = 0
        
        ip_address = commands.getoutput("/sbin/ifconfig").split("\n")[1].split()[1][5: ]

        if ("cast" in ip_address.lower()):
            ip_prefix = "Network "
            ip_address = "Not Connected"
        else:
            ip_prefix = "Website ==> http://"
            ip_address = ip_address

        filePath = '/var/www/ip.txt'
        ip_file = open(filePath,'w+')
        ip_file.write(ip_address)
        ip_file.close()

        #this file has one line, the ID
        ID_list = []
        filePath = "/var/www/ID.txt"
        file = open(filePath, 'rU')
        ID_list = file.readlines()
        file.close()
        devID = censor(ID_list[0])
        
        ser = serial.Serial()
        ser.baudrate = 9600
        ser.port = '/dev/ttyUSB0'
        ser.parity = serial.PARITY_NONE
        ser.timeout = 3
        ser.xonxoff = False
        ser.rtscts = False
        ser.open()
        time.sleep(.5)
        
        ser.write(ctrl_v + '6' + ip_prefix + maddress + ctrl_a + CR) #update variable (set variable 6 to ip_prefix)
        time.sleep(0.5)

        ser.write(ctrl_v + '4' + ip_address + maddress + ctrl_a + CR) #update variable (set variable 4 to ip_address)
        time.sleep(0.5)

        ser.write(ctrl_v + '5' + devID + maddress + ctrl_a + CR) #update variable (set variable 5 to devID)
        time.sleep(0.5)

        ser.close()
    
    if (not change_list[0] == lastChangeNum):
        lastChangeNum = change_list[0]
        if len(change_list) == 2:#only variables changed, update variables (clerkCall)
            clerkCall_list = []
            filePath = "/var/www/clerkCall.txt"
            try:
                file = open(filePath, 'rU')
                clerkCall_list = file.readlines()
                file.close()
                goOn = True
            except:
                message = "Failed to open clerkCall.txt"
                goOn = False
                clerkCallOn = False
                writeSysMsg(message)
                print(message)
            if goOn:
                clerkCallEdit1 = censor(clerkCall_list[1])
                #clerkCallEdit2 = censor(clerkCall_list[2])
                
                ser = serial.Serial()
                ser.baudrate = 9600
                ser.port = '/dev/ttyUSB0'
                ser.parity = serial.PARITY_NONE
                ser.timeout = 3
                ser.xonxoff = False
                ser.rtscts = False
                ser.open()
                time.sleep(.5)
                
                ser.write(ctrl_v + '2' + clerkCallEdit1 + maddress + ctrl_a + CR) #update variable (set variable 2)
                time.sleep(0.5)
                #ser.write(ctrl_v + '3' + clerkCallEdit2 + maddress + ctrl_a + CR) #update variable (set variable 3)
                #time.sleep(0.5)
                
                ser.close()
        else: #otherwise, must rewrite all
            #check sysDo.txt for changes, do as necessary
            filePath = "/var/www/sysDo.txt"
            try:
                file = open(filePath, 'rU')
                do_list = file.readlines()
                file.close()
            except:
                message = "Failed to open sysDo.txt"
                do_list = ["false"]
                writeSysMsg(message)
                print(message)

            if len(do_list) < 1:
                do_list = ["false"]

            elif do_list[0] == "clear":
                filePath = "/var/www/sysDo.txt"
                try:
                    file = open(filePath, 'w+')
                    file.write("false")
                    file.close()
                except:
                    message = "Failed to write sysDo.txt to false"
                    writeSysMsg(message)
                    print(message)
                
                filePath = "/var/www/sysMsg.txt"
                try:
                    file = open(filePath, 'w+')
                    file.write("This file stores helpful and exception messages for use in troubleshooting")
                    file.close()
                except:
                    message = "Failed to clear sysMsg.txt"
                    do_list = ["false"]
                    writeSysMsg(message)
                    print(message)
            elif do_list[0] == "status":
                #show various variable values
                filePath = "/var/www/sysDo.txt"
                try:
                    file = open(filePath, 'w+')
                    file.write("false")
                    file.close()
                except:
                    message = "Failed to write sysDo.txt to false"
                    writeSysMsg(message)
                    print(message)
                
                varLines = []
                varLines += ["netconnect = " + str(netconnect)]
                varLines += ["interval = " + str(interval)]
                varLines += ["timeOn = " + str(timeOn)]
                varLines += ["timeSetWeb = " + str(timeSetWeb)]
                varLines += ["timSetManual = " + str(timeSetManual)]
                varLines += ["themeOn = " + str(themeOn)]
                varLines += ["scoreOn = " + str(scoreOn)]
                varLines += ["custT1On = " + str(custT1On)]
                varLines += ["custT2On = " + str(custT2On)]
                varLines += ["clerkCallOn = " +  str(clerkCallOn)]
                varLines += ["clerkMsgOn = " + str(clerkMsgOn)]
                varLines += ["custT3On = " + str(custT3On)]
                varLines += ["devID = " + str(devID)]
                varLines += ["ip_address = " + str(ip_address)]

                for line in varLines:
                    writeSysMsg(line)          
                
            upd_marquee()


    #the following line will reset operation
    tickNum = 3600/interval #update display every hour if change.txt does not change. (to help with possible memory loss)
    if ticks == tickNum: # run upd_marquee after the alarm_handler has been called number of times
        time.sleep(0.5)
        upd_marquee()
        ticks = 0
        
    signal.alarm(interval)

def first_run():
    """
    This function is to be run at first turn on.
    It makes sure the nescessary files exist, and create them if they do not.
    It also makes sure the files required have the nescessary permissions.
    """
    #set the system to first-time run
    filePath = "/var/www/change.txt"

    try:
       text_file = open(filePath, "w")
       text_file.write("0")
       text_file.close()
    except:
        message = "Failed to set initial change condition by writing to change.txt"
        writeSysMsg(message)
        print(message)
    
    try:
        if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 666)):
            os.chmod(filePath, 0666 )#change permissions to useable by all
    except:
        message = "Unable to change permission of /var/www/change.txt"
        writeSysMsg(message)
        print(message)

    path_list = [["/var/www/theme.txt"],["/var/www/score.txt"],["/var/www/custT1.txt"],
                 ["/var/www/custT2.txt"],["/var/www/clerkCall.txt"],["/var/www/clerkMsg.txt"],
                 ["/var/www/custT3.txt"]]

    for filePath in path_list:
        filePath1 = ''.join(filePath)
        line_list = []
        try:
            file = open(filePath1, 'r')
            line_list = file.readlines()
            file.close
            permission = int(oct(stat.S_IMODE(os.lstat(filePath1).st_mode)))
            if (not (permission == 666)):
                os.chmod(filePath1, 0666)#change permissions to useable by all
            file = open(filePath1, 'w+')
            index = 0
            if len(line_list) > 0:
                for line in line_list:
                    if index == 0:
                        file.write("false")
                    else:
                        file.write("\n")
                        file.write(line)
                    index += 1
            else:
                file.write("false")
                message = str(filePath1) + " is empty. writing false."
                prin(message)
                writeSysMsg(message)
            file.close()
        except:
            new_file = open(filePath1,'w')
            new_file.write("false")
            new_file.close()
            permission = int(oct(stat.S_IMODE(os.lstat(filePath1).st_mode)))
            try:
                if (not (permission == 666)):
                    os.chmod(filePath1, 0666)#change permissions to useable
            except:
                message = "Failed to change permission on " + str(filePath1)
                print(message)
                writeSysMsg(message)
            message = str(filePath1) + " not found, writing"
            writeSysMsg(message)
            print(message)
            
    #make sure ID.txt exists
    filePath = "/var/www/ID.txt"
    try:
       file = open(filePath, 'r')
       file.close
       if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 666)):
           os.chmod(filePath, 0666)#change permissions to useable
    except:
        new_file = open(filePath,'w+')
        new_file.write("Generic")
        new_file.close()
        try:
            if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 666)):
                os.chmod(filePath, 0666)#change permissions to useable
        except:
            message = "Failed to change permission on " + str(filePath)
            print(message)
            writeSysMsg(message)
        message = "ID.txt not found, writing ID.txt to default value"
        writeSysMsg(message)
        print(message)

    #Make sure defTheme.txt exists
    filePath = "/var/www/defTheme.txt"  
    try:
       file = open(filePath, 'r')
       file.close
       if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 666)):
           os.chmod(filePath, 0666)#change permissions to useable
    except:
        new_file = open(filePath,'w+')
        new_file.write("Welcome")
        new_file.close()
        try:
            if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 666)):
                os.chmod(filePath, 0666)#change permissions to useable
        except:
            message = "Failed to change permission on " + str(filePath)
            print(message)
            writeSysMsg(message)
        message = "defTheme.txt not found, writing defTheme.txt to default value"
        writeSysMsg(message)
        print(message)

    #Make sure cenLev.txt exists
    filePath = "/var/www/cenLev.txt"  
    try:
       file = open(filePath, 'r')
       file.close
       if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 666)):
           os.chmod(filePath, 0666)#change permissions to useable
    except:
        new_file = open(filePath,'w+')
        new_file.write("3")
        new_file.close()
        try:
            if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 666)):
                os.chmod(filePath, 0666)#change permissions to useable
        except:
            message = "Failed to change permission on " + str(filePath)
            print(message)
            writeSysMsg(message)
        message = "cenLev.txt not found, writing cenLev.txt"
        writeSysMsg(message)
        print(message)


    #Make sure ip.txt exists
    filePath = "/var/www/ip.txt"
    try:
       file = open(filePath, 'r')
       file.close
       if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 666)):
           os.chmod(filePath, 0666)#change permissions to useable
    except:
        new_file = open(filePath,'w+')
        new_file.write("ip_address")
        new_file.close()
        try:
            if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 666)):
                os.chmod(filePath, 0666)#change permissions to useable
        except:
            message = "Failed to change permission on " + str(filePath)
            print(message)
            writeSysMsg(message)
        message = "ip.txt not found, writing ip.txt"
        writeSysMsg(message)
        print(message)

    #Make sure status.txt exists, and set the first line to initial boot
    filePath = "/var/www/status.txt"
    try:
        file = open(filePath, 'w+')
        file.write("Computer System Booted")
        file.close()
        if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 666)):
           os.chmod(filePath, 0666)#change permissions to useable

    except:
        messsage = "Failed to write initial message to status.txt"
        writeSysMsg(message)
        print(message)

    #Make sure time.txt exists, and set the first line to false
    filePath = "/var/www/time.txt"
    try:
        file = open(filePath, 'w+')
        file.write("false")
        file.close()
        if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 666)):
           os.chmod(filePath, 0666)#change permissions to useable

    except:
        message = "Failed to write initial message to time.txt"
        writeSysMsg(message)
        print(message)

    #Make sure internet.txt exists, and set the first line to false
    filePath = "/var/www/internet.txt"
    try:
        file = open(filePath, 'w+')
        file.write("false")
        file.close()
        if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 666)):
           os.chmod(filePath, 0666)#change permissions to useable

    except:
        message = "Failed to write initial message to internet.txt"
        writeSysMsg(message)
        print(message)

    #Make sure sysDo.txt exists, and set the first line to false
    filePath = "/var/www/sysDo.txt"
    try:
        file = open(filePath, 'w+')
        file.write("false")
        file.close()
        if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 666)):
           os.chmod(filePath, 0666)#change permissions to executable by all
    except:
        message = "Failed to write initial message to sysDo.txt"
        writeSysMsg(message)
        print(message)
    
    #Make sure php files are executable by all
    filePath = "/var/www/ajax_handler.php"
    try:
        if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 777)):
           os.chmod(filePath, 0777)#change permissions to executable by all
    except:
        message = "failed to change permissions on ajax_handler.php"
        writeSysMsg(message)
        print(message)

    filePath = "/var/www/index.php"
    try:
        if (not (int(oct(stat.S_IMODE(os.lstat(filePath).st_mode))) == 777)):
           os.chmod(filePath, 0777)#change permissions to executable by all
    except:
        message = "failed to change permissions on ajax_handler.php"
        writeSysMsg(message)
        print(message)

    check_network() #check network, use to decide whether or not to use user inputed time


# main
# ========================================================================
if __name__ == "__main__":
    """
    print "THIS IS A BEN TEST"
    
    ser = serial.Serial()
    ser.baudrate = 9600
    ser.port = '/dev/ttyUSB0'
    ser.parity = serial.PARITY_NONE
    ser.timeout = 3
    ser.xonxoff = False
    ser.rtscts = False
    ser.open()
    
    ser.write(ctrl_s + maddress + ctrl_a + CR) #clear auto repeat buffer
    ser.write(ctrl_y + ctrl_r + maddress + '2' + CR)
    ser.write(ctrl_x + ctrl_a + ctrl_a + CR)
    #upd_marquee()
    """
#else:
    #Make sure sysMsg.txt exists
    filePath = "/var/www/sysMsg.txt"
    try:
       file = open(filePath, 'r')
       file.close
    except:
        new_file = open(filePath,'w+')
        new_file.write("This file stores helpful and exception messages for use in troubleshooting")
        new_file.close()
        os.chmod(filePath, 0666)#change permissions to useable
        print("Writing sysMsg.txt")
    writeSysMsg("=======================================================")
    message = 'Marquee started at ' + time.strftime('%a %d-%b-%y %H:%M') + " (system time)"
    writeSysMsg(message)
    print(message)
 
    
    # update the marquee for the first time
    #upd_marquee()
    first_run()

    time.sleep(3)

    # start the periodic timer
    signal.signal(signal.SIGALRM, alarm_handler)
    signal.alarm(interval)
    
    # start the continuous loop
    while True:
        #pass #this keeps app running, but causes huge CPU usage
        time.sleep(1) #keeps app running, uses little CPU

