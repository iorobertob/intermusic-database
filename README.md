# Media Posters List #

### Important: 
- This module only lists activities of the Media Poster plugin, which is based in the Poster pugin. 
- When installing the folder of this module has to be named 'inter' 


## What it does
This module will look for the instances of our custom [Media Poster plugin](https://github.com/iorobertob/intermusic_mposter) in the current course, or across the whole Moodle platform, and display a searchable list of them. 

It will take the metadata set in the configuration of a the Media Posters (manually input or retreived from ResourceSpace, Asset Management System *AMS*) and populate a table, with links to the corresponding mposter. 

The data is automatically updated via cron tasks and everytime the module is saved to synchonize with new mposters created/removed. 

The name of the columns for the metadata of the Media Poster can be automatically populated with defalt values or overriden with custom values in the configuration section.


Intermusic Project
----------
This module was created as part of the [Intermusic Project](https://intermusic.lmta.lt). Its functionality reflects the needs of the project, but it is also intended to work away from that context and use the metadata features more generally. 


Installation
----------
Standard moodle plugin process, though the admin pages or by copying this repository in a folder named **mposter** inside **mod/**



