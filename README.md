# pw-autoexport-tf - AutoExportTemplatesAndFields module for ProcessWire
## Installation

To install this module manually, simply put it into the site/modules directory
of your ProcessWire installation, so that directory should look like this:

    site/modules/
      AutoExportTemplatesAndFields/
        AutoExportTemplatesAndFields.module
        cli/
           import.php
      ...

## Usage
### Automatic export when changing fields/templates

After installing the module, all changes to fields and templates are
automatically exported after the respective request to the directory specified
via the module's persistDirectory setting.

### Import via command line

In order to facilitate executing the import as part of an automated process, it
has been implemented as a command line application and can be called as
follows (assuming the php executable can be called as `php` and the working
directory is the root of the ProcessWire installation):

    php site\modules\AutoExportTemplatesAndFields\cli\import.php --import

The import automatically creates a database backup which can be restored using:

    php site\modules\AutoExportTemplatesAndFields\cli\import.php --restore <path>

### BUGS

 * (#1) This module does not seem to work well together with ProFields or when using
repeater fields. See this forum thread:

  https://processwire.com/talk/topic/13758-autoexporttemplatesandfields-enables-continuous-integration-of-template-and-field-configuration-changes/

## Contact

I am looking forward to hearing from you: Contact me
<michael.jaros@foxcraft.at> for any issues or suggestions regarding this module
or just drop an issue on github: 

  https://github.com/jaromic/pw-autoexport-tf/issues
