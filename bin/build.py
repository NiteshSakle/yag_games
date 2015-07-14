#!/usr/bin/env python2
# -*- coding: utf-8 -*-

# build.py
# @author: Venugopal

# import
import os
import sys
from optparse import OptionParser
import subprocess
import shutil

_projectDir = os.path.abspath(os.path.dirname(__file__)) + '/..'
_environment = 'prod';

def build():
    resolveEnvironmentFromCommandLineParameter()
    
    printAndFlush('== STARTING BUILD ==')

    # create files
    printAndFlush('== CREATING FILES from DIST ==') 
    for file in ['local.php', 'db.local.php', 'mail.local.php', 'phpSettings.local.php']:        
        copyFileIfDestDoesNotExist(_projectDir + '/config/autoload/' + file + '.dist', _projectDir + '/config/autoload/' + file)

    # create folders
    printAndFlush('== CREATING FOLDERS ==') 
    folders = ['data/cache', 'data/logs']
    for dir in folders:
        createDirIfDoesNotExist(_projectDir + '/' + dir)

        if(_environment in ['integration', 'staging', 'test', 'dev']):
            deleteFolderContent(_projectDir + '/' + dir)
    
    # Install vendors
    printAndFlush('== Vendors build ==')
    if not os.path.exists(_projectDir + '/vendor'):        
	executeCommandlineInSubprocess('curl -s http://getcomposer.org/installer | php')
	
    executeCommandlineInSubprocess('php composer.phar install')

    printAndFlush('== ENDING BUILD ==')

def resolveEnvironmentFromCommandLineParameter():
    parser = OptionParser()
    parser.add_option('', '--env', dest='env', help='Which environment should be used', default='prod', metavar="ENVIRONMENT")
    
    global _environment
    
    (options, args) = parser.parse_args()
    _environment = options.env
    
    # Check for correct environment, throw error if not correct
    if (_environment not in ['prod', 'integration', 'staging', 'test', 'dev']):
        sys.exit('Wrong environment')

def printAndFlush(text):
    print text
    sys.stdout.flush()
    
def copyFileIfDestDoesNotExist(src, dest):
    if not(os.path.exists(dest)):
        shutil.copy(src, dest)
        
def createDirIfDoesNotExist(dir):
    if not os.path.exists(dir):
        os.makedirs(dir)
    
def deleteFolderContent(dir):
    if not os.path.isdir(dir):
        return

    for object in os.listdir(dir):
        try:
            objectPath = os.path.join(dir, object)
            if os.path.isfile(objectPath):
                os.unlink(objectPath)
            else:
                shutil.rmtree(objectPath)
        except:
            print '!!! Delete files failed !!!'
            e,p,t= sys.exc_info()
            print e,p
    
def executeCommandlineInSubprocess(command):
    try:
        subprocess.call(command, stdout=subprocess.PIPE, shell=True)
    except Exception as (errno, strerror):
        printAndFlush(strerror + ' (' + str(errno) + ')')

# Make sure this can be used on command line
if __name__ == '__main__':
    build()