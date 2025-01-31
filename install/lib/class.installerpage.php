<?php

/**
 * @package content
 */
class InstallerPage extends HTMLPage
{
    private $template;
    protected $params;
    protected $page_title;

    public function __construct($template, $params = [])
    {
        parent::__construct();

        $this->template = $template;
        $this->params = $params;

        $this->page_title = __('Install Symphony');
    }

    public function generate($page = null)
    {
        $this->Html->setDTD('<!DOCTYPE html>');
        $this->Html->setAttribute('lang', Lang::get());

        $this->addHeaderToPage('Cache-Control', 'no-cache, must-revalidate, max-age=0');
        $this->addHeaderToPage('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');

        $this->setTitle($this->page_title);
        $this->addElementToHead(new XMLElement('meta', null, array('charset' => 'UTF-8')), 1);
        $this->addElementToHead(new XMLElement('meta', null, array('name' => 'robots', 'content' => 'noindex')), 2);

        $this->addStylesheetToHead(APPLICATION_URL . '/assets/css/installer.min.css', 'screen', 30);

        return parent::generate($page);
    }

    protected function __build($version = VERSION, XMLElement $extra = null)
    {
        parent::__build();

        $this->Form = Widget::Form(INSTALL_URL . '/index.php', 'post');

        $title = new XMLElement('h1', $this->page_title);
        $version = new XMLElement('em', __('Version %s', array($version)));

        $title->appendChild($version);

        if (!is_null($extra)) {
            $title->appendChild($extra);
        }

        $this->Form->appendChild($title);

        if (isset($this->params['show-languages']) && $this->params['show-languages']) {
            $languages = new XMLElement('ul');

            foreach (Lang::getAvailableLanguages(false) as $code => $lang) {
                $languages->appendChild(new XMLElement(
                    'li',
                    Widget::Anchor(
                        $lang,
                        '?lang=' . $code
                    ),
                    ($_REQUEST['lang'] == $code || ($_REQUEST['lang'] == null && $code == 'en'))
                        ? ['class' => 'selected']
                        : []
                ));
            }

            $languages->appendChild(new XMLElement(
                'li',
                Widget::Anchor(
                    __('Symphony is also available in other languages'),
                    'http://getsymphony.com/download/extensions/translations/'
                ),
                array('class' => 'more')
            ));

            $this->Form->appendChild($languages);
        }

        $this->Body->appendChild($this->Form);

        $function = 'view' . str_replace('-', '', ucfirst($this->template));
        $this->$function();
    }

    protected function viewMissingLog()
    {
        $h2 = new XMLElement('h2', __('Missing log file'));

        // What folder wasn't writable? The docroot or the logs folder?
        // RE: #1706
        if (is_writeable(DOCROOT) === false) {
            $folder = DOCROOT;
        } elseif (is_writeable(MANIFEST) === false) {
            $folder = MANIFEST;
        } elseif (is_writeable(INSTALL_LOGS) === false) {
            $folder = INSTALL_LOGS;
        }

        $p = new XMLElement('p', __('Symphony tried to create a log file and failed. Make sure the %s folder is writable.', array('<code>' . $folder . '</code>')));

        $this->Form->appendChild($h2);
        $this->Form->appendChild($p);
        $this->setHttpStatus(Page::HTTP_STATUS_ERROR);
    }

    protected function viewRequirements()
    {
        $h2 = new XMLElement('h2', __('System Requirements'));

        $this->Form->appendChild($h2);

        if (!empty($this->params['errors'])) {
            $div = new XMLElement('div');
            $this->__appendError(array_keys($this->params['errors']), $div, __('Symphony needs the following requirements to be met before things can be taken to the “next level”.'));

            $this->Form->appendChild($div);
        }
        $this->setHttpStatus(Page::HTTP_STATUS_ERROR);
    }

    protected function viewLanguages()
    {
        $h2 = new XMLElement('h2', __('Language selection'));
        $p = new XMLElement('p', __('This installation can speak in different languages. Which one are you fluent in?'));

        $this->Form->appendChild($h2);
        $this->Form->appendChild($p);

        $languages = [];

        foreach (Lang::getAvailableLanguages(false) as $code => $lang) {
            $languages[] = array($code, ($code === 'en'), $lang);
        }

        if (count($languages) > 1) {
            $languages[0][1] = false;
            $languages[1][1] = true;
        }

        $this->Form->appendChild(Widget::Select('lang', $languages));

        $Submit = new XMLElement('div', null, array('class' => 'submit'));
        $Submit->appendChild(Widget::Input('action[proceed]', __('Proceed with installation'), 'submit'));

        $this->Form->appendChild($Submit);
    }

    protected function viewFailure()
    {
        $h2 = new XMLElement('h2', __('Installation Failure'));
        $p = new XMLElement('p', __('An error occurred during installation.'));

        // Attempt to get log information from the log file
        try {
            $log = file_get_contents(INSTALL_LOGS . '/install');
        } catch (Exception $ex) {
            $log_entry = Symphony::Log()->popFromLog();
            if (isset($log_entry['message'])) {
                $log = $log_entry['message'];
            } else {
                $log = 'Unknown error occurred when reading the install log';
            }
        }

        $code = new XMLElement('code', $log);

        $this->Form->appendChild($h2);
        $this->Form->appendChild($p);
        $this->Form->appendChild(
            new XMLElement('pre', $code)
        );
        $this->setHttpStatus(Page::HTTP_STATUS_ERROR);
    }

    protected function viewSuccess()
    {
        $symphonyUrl = URL . '/' . Symphony::Configuration()->get('admin-path', 'symphony');
        $this->Form->setAttribute('action', $symphonyUrl);

        $div = new XMLElement('div');
        $div->appendChild(
            new XMLElement('h2', __('The floor is yours'))
        );
        $div->appendChild(
            new XMLElement('p', __('Thanks for taking the quick, yet epic installation journey with us. It’s now your turn to shine!'))
        );
        $this->Form->appendChild($div);

        $ul = new XMLElement('ul');
        foreach ($this->params['disabled-extensions'] as $handle) {
            $ul->appendChild(
                new XMLElement('li', '<code>' . $handle . '</code>')
            );
        }

        if ($ul->getNumberOfChildren() !== 0) {
            $this->Form->appendChild(
                new XMLElement('p',
                    __('Looks like the following extensions couldn’t be enabled and must be manually installed. It’s a minor setback in our otherwise prosperous future together.')
                )
            );
            $this->Form->appendChild($ul);
        }

        $this->Form->appendChild(
            new XMLElement('p',
                __('I think you and I will achieve great things together. Just one last thing: please %s to secure the safety of our relationship.', array(
                        '<a href="' . URL . '/install/?action=remove">' .
                        __('remove the %s folder', array('<code>' . basename(INSTALL) . '</code>')) .
                        '</a>'
                    )
                )
            )
        );

        $submit = new XMLElement('div', null, array('class' => 'submit'));
        $submit->appendChild(Widget::Input('submit', __('Okay, now take me to the login page'), 'submit'));

        $this->Form->appendChild($submit);
    }

    protected function viewConfiguration()
    {
        // Populating fields array
        $fields = isset($_POST['fields']) ? $_POST['fields'] : $this->params['default-config'];

        // Welcome
        $div = new XMLElement('div');
        $div->appendChild(
            new XMLElement('h2', __('Find something sturdy to hold on to because things are about to get awesome.'))
        );
        $div->appendChild(
            new XMLElement('p', __('Think of this as a pre-game warm up. You know you’re going to kick-ass, so you’re savouring every moment before the show. Welcome to the Symphony install page.'))
        );

        $this->Form->appendChild($div);

        if (!empty($this->params['errors'])) {
            $this->Form->appendChild(
                Widget::Error(new XMLElement('p'), __('Oops, a minor hurdle on your path to glory! There appears to be something wrong with the details entered below.'))
            );
        }

        // Environment settings
        $fieldset = new XMLElement('fieldset');
        $div = new XMLElement('div');
        $this->__appendError(array('no-write-permission-root', 'no-write-permission-workspace'), $div);
        if ($div->getNumberOfChildren() > 0) {
            $fieldset->appendChild($div);
            $this->Form->appendChild($fieldset);
        }

        // Website & Locale settings
        $Environment = new XMLElement('fieldset');
        $Environment->appendChild(new XMLElement('legend', __('Website Preferences')));

        $label = Widget::Label(__('Name'), Widget::Input('fields[general][sitename]', $fields['general']['sitename']));

        $this->__appendError(array('general-no-sitename'), $label);
        $Environment->appendChild($label);

        $label = Widget::Label(__('Admin Path'), Widget::Input('fields[symphony][admin-path]', $fields['symphony']['admin-path']));

        $this->__appendError(array('no-symphony-path'), $label);
        $Environment->appendChild($label);

        $Fieldset = new XMLElement('fieldset', null, array('class' => 'frame'));
        $Fieldset->appendChild(new XMLElement('legend', __('Date and Time')));
        $Fieldset->appendChild(new XMLElement('p', __('Customise how Date and Time values are displayed throughout the Administration interface.')));

        // Timezones
        $options = DateTimeObj::getTimezonesSelectOptions((
            !empty($fields['region']['timezone'])
                ? $fields['region']['timezone']
                : date_default_timezone_get()
        ));
        $Fieldset->appendChild(Widget::Label(__('Region'), Widget::Select('fields[region][timezone]', $options)));

        // Date formats
        $options = DateTimeObj::getDateFormatsSelectOptions($fields['region']['date_format']);
        $Fieldset->appendChild(Widget::Label(__('Date Format'), Widget::Select('fields[region][date_format]', $options)));

        // Time formats
        $options = DateTimeObj::getTimeFormatsSelectOptions($fields['region']['time_format']);
        $Fieldset->appendChild(Widget::Label(__('Time Format'), Widget::Select('fields[region][time_format]', $options)));

        $Environment->appendChild($Fieldset);
        $this->Form->appendChild($Environment);

        // Database settings
        $Database = new XMLElement('fieldset');
        $Database->appendChild(new XMLElement('legend', __('Database Connection')));
        $Database->appendChild(new XMLElement('p', __('Please provide Symphony with access to a database.')));

        // Database name
        $label = Widget::Label(__('Database'), Widget::Input('fields[database][db]', $fields['database']['db']));

        $this->__appendError(array('database-incorrect-version', 'unknown-database'), $label);
        $Database->appendChild($label);

        // Database credentials
        $Div = new XMLElement('div', null, array('class' => 'two columns'));
        $Div->appendChild(Widget::Label(__('Username'), Widget::Input('fields[database][user]', $fields['database']['user']), 'column'));
        $Div->appendChild(Widget::Label(__('Password'), Widget::Input('fields[database][password]', $fields['database']['password'], 'password'), 'column'));

        $this->__appendError(array('database-invalid-credentials'), $Div);
        $Database->appendChild($Div);

        // Advanced configuration
        $Fieldset = new XMLElement('fieldset', null, array('class' => 'frame'));
        $Fieldset->appendChild(new XMLElement('legend', __('Advanced Configuration')));
        $Fieldset->appendChild(new XMLElement('p', __('Leave these fields unless you are sure they need to be changed.')));

        // Advanced configuration: Host, Port
        $Div = new XMLElement('div', null, array('class' => 'two columns'));
        $Div->appendChild(Widget::Label(__('Host'), Widget::Input('fields[database][host]', $fields['database']['host']), 'column'));
        $Div->appendChild(Widget::Label(__('Port'), Widget::Input('fields[database][port]', $fields['database']['port']), 'column'));

        $this->__appendError(array('no-database-connection'), $Div);
        $Fieldset->appendChild($Div);
        $Fieldset->appendChild(new XMLElement('p', __('It is recommend to use <code>localhost</code> or <code>unix_socket</code> over <code>127.0.0.1</code> as the host on production servers.') . ' ' . __('The port field can be used to specify the UNIX socket path.')));

        // Advanced configuration: Table Prefix
        $label = Widget::Label(__('Table Prefix'), Widget::Input('fields[database][tbl_prefix]', $fields['database']['tbl_prefix']));

        $this->__appendError(array('database-table-prefix'), $label);
        $Fieldset->appendChild($label);

        $Database->appendChild($Fieldset);
        $this->Form->appendChild($Database);

        // Permission settings
        $Permissions = new XMLElement('fieldset');
        $Permissions->appendChild(new XMLElement('legend', __('Permission Settings')));
        $Permissions->appendChild(new XMLElement('p', __('Set the permissions Symphony uses when saving files/directories.')));

        $Div = new XMLElement('div', null, array('class' => 'two columns'));
        $Div->appendChild(Widget::Label(__('Files'), Widget::Input('fields[file][write_mode]', $fields['file']['write_mode']), 'column'));
        $Div->appendChild(Widget::Label(__('Directories'), Widget::Input('fields[directory][write_mode]', $fields['directory']['write_mode']), 'column'));

        $Permissions->appendChild($Div);
        $this->Form->appendChild($Permissions);

        // User settings
        $User = new XMLElement('fieldset');
        $User->appendChild(new XMLElement('legend', __('User Information')));
        $User->appendChild(new XMLElement('p', __('Once installation is complete, you will be able to log in to the Symphony admin area with these user details.')));

        if (!isset($fields['user'])) {
            $fields['user'] = array(
                'username' => '',
                'password' => '',
                'confirm-password' => '',
                'firstname' => '',
                'lastname' => '',
                'email' => ''
            );
        }

        // Username
        $label = Widget::Label(__('Username'), Widget::Input('fields[user][username]', $fields['user']['username']));

        $this->__appendError(array('user-no-username'), $label);
        $User->appendChild($label);

        // Password
        $Div = new XMLElement('div', null, array('class' => 'two columns'));
        $Div->appendChild(Widget::Label(__('Password'), Widget::Input('fields[user][password]', $fields['user']['password'], 'password'), 'column'));
        $Div->appendChild(Widget::Label(__('Confirm Password'), Widget::Input('fields[user][confirm-password]', $fields['user']['confirm-password'], 'password'), 'column'));

        $this->__appendError(array('user-no-password', 'user-password-mismatch'), $Div);
        $User->appendChild($Div);

        // Personal information
        $Fieldset = new XMLElement('fieldset', null, array('class' => 'frame'));
        $Fieldset->appendChild(new XMLElement('legend', __('Personal Information')));
        $Fieldset->appendChild(new XMLElement('p', __('Please add the following personal details for this user.')));

        // Personal information: First Name, Last Name
        $Div = new XMLElement('div', null, array('class' => 'two columns'));
        $Div->appendChild(Widget::Label(__('First Name'), Widget::Input('fields[user][firstname]', $fields['user']['firstname']), 'column'));
        $Div->appendChild(Widget::Label(__('Last Name'), Widget::Input('fields[user][lastname]', $fields['user']['lastname']), 'column'));

        $this->__appendError(array('user-no-name'), $Div);
        $Fieldset->appendChild($Div);

        // Personal information: Email Address
        $label = Widget::Label(__('Email Address'), Widget::Input('fields[user][email]', $fields['user']['email']));

        $this->__appendError(array('user-invalid-email'), $label);
        $Fieldset->appendChild($label);

        $User->appendChild($Fieldset);
        $this->Form->appendChild($User);

        // Submit area
        $this->Form->appendChild(new XMLElement('h2', __('Install Symphony')));
        $this->Form->appendChild(new XMLElement('p', __('The installation process goes by really quickly. Make sure to take a deep breath before you press that sweet button.', array('<code>' . basename(INSTALL_URL) . '</code>'))));

        $Submit = new XMLElement('div', null, array('class' => 'submit'));
        $Submit->appendChild(Widget::Input('lang', Lang::get(), 'hidden'));

        $Submit->appendChild(Widget::Input('action[install]', __('Install Symphony'), 'submit'));

        $this->Form->appendChild($Submit);

        if (!empty($this->params['errors'])) {
            $this->setHttpStatus(Page::HTTP_STATUS_BAD_REQUEST);
        }
    }

    private function __appendError(array $codes, XMLElement &$element, $message = null)
    {
        if (is_null($message)) {
            $message =  __('The following errors have been reported:');
        }

        foreach ($codes as $i => $c) {
            if (!isset($this->params['errors'][$c])) {
                unset($codes[$i]);
            }
        }

        if (!empty($codes)) {
            if (count($codes) > 1) {
                $ul = new XMLElement('ul');

                foreach ($codes as $c) {
                    if (isset($this->params['errors'][$c])) {
                        $ul->appendChild(new XMLElement('li', $this->params['errors'][$c]['details']));
                    }
                }

                $element = Widget::Error($element, $message);
                $element->appendChild($ul);
            } else {
                $code = array_pop($codes);

                if (isset($this->params['errors'][$code])) {
                    $element = Widget::Error($element, $this->params['errors'][$code]['details']);
                }
            }
        }
    }
}
