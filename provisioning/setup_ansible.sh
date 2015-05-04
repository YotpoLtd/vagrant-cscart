#!/bin/bash

## Enable PPA and install Ansible

# from http://www.ansibleworks.com/docs/intro_installation.html#latest-releases-via-apt-ubuntu

apt-get update -qq --yes
apt-get install -qq --yes python-software-properties
add-apt-repository --yes ppa:rquillo/ansible
apt-get update --yes -qq
apt-get upgrade -qq --force-yes
apt-get install --yes ansible python-apt
