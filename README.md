# pw-autoexport-tf
## AutoExportTemplatesAndFields module for ProcessWire
### Auto-Export during 

After installing the module, all changes to fields, fieldgroups, and templates
are automatically exported after the respective request to the directory
specified via the module's persistDirectory setting.

### Import via command line

In order to facilitate executing the import as part of an automated process, it
has been implemented as a command line application and can be called as
follows (assuming the php executable can be called as `php` and the working
directory is the root of the ProcessWire installation):

    php site\modules\AutoExportTemplatesAndFields\cli\import.php

## Contact

I am looking forward to hearing from you: Contact Michael Jaros
<michael.jaros@jarosoft.at> for any issues or suggestions regarding this
module.
