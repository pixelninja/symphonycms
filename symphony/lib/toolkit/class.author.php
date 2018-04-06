<?php
/**
 * @package toolkit
 */
/**
 * The Author class represents a Symphony Author object. Authors are
 * the backend users in Symphony.
 *
 * @since Symphony 3.0.0 it implements the ArrayAccess interface.
 */
class Author implements ArrayAccess
{
    /**
     * An associative array of information relating to this author where
     * the keys map directly to the `tbl_authors` columns.
     * @var array
     */
    private $fields = [];

    /**
     * Stores a key => value pair into the Author object's `$this->fields` array.
     *
     * @param string $field
     *  Maps directly to a column in the `tbl_authors` table.
     * @param string $value
     *  The value for the given $field
     */
    public function set($field, $value)
    {
        $field = trim($field);
        if ($value === null) {
            $this->fields[$field] = null;
        } else {
            $this->fields[$field] = trim($value);
        }
    }

    /**
     * Retrieves the value from the Author object by field from `$this->fields`
     * array. If field is omitted, all fields are returned.
     *
     * @param string $field
     *  Maps directly to a column in the `tbl_authors` table. Defaults to null
     * @return mixed
     *  If the field is not set or is empty, returns null.
     *  If the field is not provided, returns the `$this->fields` array
     *  Otherwise returns a string.
     */
    public function get($field = null)
    {
        if (is_null($field)) {
            return $this->fields;
        }

        if (!isset($this->fields[$field]) || $this->fields[$field] == '') {
            return null;
        }

        return $this->fields[$field];
    }

    /**
     * Given a field, remove it from `$this->fields`
     *
     * @since Symphony 2.2.1
     * @param string $field
     *  Maps directly to a column in the `tbl_authors` table. Defaults to null
     */
    public function remove($field = null)
    {
        if (!is_null($field)) {
            return;
        }

        unset($this->fields[$field]);
    }

    /**
     * Implementation of ArrayAccess::offsetExists()
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->fields[$offset]);
    }

    /**
     * Implementation of ArrayAccess::offsetGet()
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->fields[$offset];
    }

    /**
     * Implementation of ArrayAccess::offsetSet()
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->fields[$offset] = $value;
    }

    /**
     * Implementation of ArrayAccess::offsetUnset()
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->fields[$offset]);
    }

    /**
     * Sets all the fields values from the database for this extension.
     *
     * @param array $fields
     * @return void
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * Returns boolean if the current Author is the original creator
     * of this Symphony installation.
     *
     * @return boolean
     */
    public function isPrimaryAccount()
    {
        return ($this->get('primary') === 'yes');
    }

    /**
     * Returns boolean if the current Author is of the developer
     * user type.
     *
     * @return boolean
     */
    public function isDeveloper()
    {
        return ($this->get('user_type') == 'developer');
    }

    /**
     * Returns boolean if the current Author is of the manager
     * user type.
     *
     * @since  2.3.3
     * @return boolean
     */
    public function isManager()
    {
        return ($this->get('user_type') == 'manager');
    }

    /**
     * Returns boolean if the current Author is of the author
     * user type.
     *
     * @since  2.4
     * @return boolean
     */
    public function isAuthor()
    {
        return ($this->get('user_type') == 'author');
    }

    /**
     * Returns boolean if the current Author's authentication token
     * is active or not.
     *
     * @return boolean
     */
    public function isTokenActive()
    {
        return ($this->get('auth_token_active') === 'yes' ? true : false);
    }

    /**
     * A convenience method that returns an Authors full name
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->get('first_name') . ' ' . $this->get('last_name');
    }

    /**
     * Creates an author token using the `Cryptography::hash` function and the
     * current Author's username and password. The default hash function
     * is SHA1
     *
     * @see toolkit.Cryptography#hash()
     * @see toolkit.General#substrmin()
     *
     * @return string
     */
    public function createAuthToken()
    {
        return General::substrmin(sha1($this->get('username') . $this->get('password')), 8);
    }

    /**
     * Prior to saving an Author object, the validate function ensures that
     * the values in `$this->fields` array are correct. As of Symphony 2.3
     * Authors must have unique username AND email address. This function returns
     * boolean, with an `$errors` array provided by reference to the callee
     * function.
     *
     * @param array $errors
     * @return boolean
     */
    public function validate(&$errors)
    {
        $errors = array();
        $current_author = null;

        if (is_null($this->get('first_name'))) {
            $errors['first_name'] = __('First name is required');
        }

        if (is_null($this->get('last_name'))) {
            $errors['last_name'] = __('Last name is required');
        }

        if ($this->get('id')) {
            $current_author = Symphony::Database()
                ->select(['email', 'username'])
                ->from('tbl_authors')
                ->where(['id' => $this->get('id')])
                ->execute()
                ->next();
        }

        // Include validators
        include TOOLKIT . '/util.validators.php';

        // Check that Email is provided
        if (is_null($this->get('email'))) {
            $errors['email'] = __('E-mail address is required');

            // Check Email is valid
        } elseif (isset($validators['email']) && !General::validateString($this->get('email'), $validators['email'])) {
            $errors['email'] = __('E-mail address entered is invalid');

            // Check Email is valid, fallback when no validator found
        } elseif (!isset($validators['email']) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = __('E-mail address entered is invalid');

            // Check that if an existing Author changes their email address that
            // it is not already used by another Author
        } elseif ($this->get('id')) {
            if (
                $current_author['email'] !== $this->get('email') &&
                (int)Symphony::Database()
                    ->selectCount()
                    ->from('tbl_authors')
                    ->where(['email' => $this->get('email')])
                    ->limit(1)
                    ->execute()
                    ->variable(0) !== 0
            ) {
                $errors['email'] = __('E-mail address is already taken');
            }

            // Check that Email is not in use by another Author
        } elseif (Symphony::Database()
                  ->select(['id'])
                  ->from('tbl_authors')
                  ->where(['email' => $this->get('email')])
                  ->limit(1)
                  ->execute()
                  ->variable('id')) {
            $errors['email'] = __('E-mail address is already taken');
        }

        // Check the username exists
        if (is_null($this->get('username'))) {
            $errors['username'] = __('Username is required');

        // Check that if it's an existing Author that the username is not already
        // in use by another Author if they are trying to change it.
        } elseif ($this->get('id')) {
            if (
                $current_author['username'] !== $this->get('username') &&
                (int)Symphony::Database()
                    ->selectCount()
                    ->from('tbl_authors')
                    ->where(['username' => $this->get('username')])
                    ->limit(1)
                    ->execute()
                    ->variable(0) !== 0
            ) {
                $errors['username'] = __('Username is already taken');
            }

            // Check that the username is unique
        } elseif (Symphony::Database()
                    ->select(['id'])
                    ->from('tbl_authors')
                    ->where(['username' => $this->get('username')])
                    ->limit(1)
                    ->execute()
                    ->variable('id')) {
            $errors['username'] = __('Username is already taken');
        }

        if (is_null($this->get('password'))) {
            $errors['password'] = __('Password is required');
        }

        return (empty($errors) ? true : false);
    }

    /**
     * This is the insert method for the Author. This takes the current
     * `$this->fields` values and adds them to the database using either the
     * `AuthorManager::edit` or `AuthorManager::add` functions. An
     * existing user is determined by if an ID is already set.
     *
     * @see toolkit.AuthorManager#add()
     * @see toolkit.AuthorManager#edit()
     * @return integer|boolean
     *  When a new Author is added or updated, an integer of the Author ID
     *  will be returned, otherwise false will be returned for a failed update.
     */
    public function commit()
    {
        if (!is_null($this->get('id'))) {
            $id = $this->get('id');
            $this->remove('id');

            if (AuthorManager::edit($id, $this->get())) {
                $this->set('id', $id);
                return $id;
            } else {
                return false;
            }
        } else {
            return AuthorManager::add($this->get());
        }
    }
}
