# Patient Sharing

GemsTracker assigns patients to organisations and which patients a user can search depends on his or her
current organisation. However: when a patient is searchable and thus visible for a user it is possible the data
in other organisations becomes visible as well.

## Implementation using $forOtherOrgs

The respondent controller snippets actions that have the `$forOtherOrgs` property may show information from multiple 
organizations. `$forOtherOrgs` is either:
- `false` (show no other organizations), 
- `true` (show all other organizations this respondent belongs to)
- an `array` of organization ids, including the current.

`$forOtherOrgs` is filled by the `getOtherOrgs()` function in `Gems_Default_RespondentNewAction` which by default uses 
the `getOtherOrgsFor` function from `Util.php` that uses the current patient's organization id as a parameter:

```php
    /**
     * The organizations whose tokens and tracks are shown for this organization
     *
     * When true: show tokens for all organizations, false: only current organization, array => those organizations
     *
     * @param int $forOrgId Optional, uses current user when empty
     * @return boolean|array
     */
    public function getOtherOrgsFor($forOrgId = null)
    {
        // Do not show data from other orgs
        return false;

        // Do show data from all other orgs
        // return true;

        // Return the organizations the user is allowed to see.
        // return array_keys($this->currentUser->getAllowedOrganizations());
    }
```

## Overruling `getOtherOrgsFor()`

The Pulse implementation is a good example of overruling the allowed organization by overruling the `getOtherOrgsFor()` 
function. Basically for certain organization codes it adds extra organizations to make visible. 

```php
    /**
     * The organizations whose tokens are shown for this organization
     *
     * When true: show tokens for all organizations, false: only current organisation, array => those organisations
     *
     * @param int $organizationId Optional, uses current user when empty
     * @return boolean|array
     */
    public function getOtherOrgsFor($forOrgId = null)
    {
        $currentOrganization = $this->loader->getOrganization($forOrgId);

        if ($currentOrganization->containsCode('xpert-handtherapie')) {
            return array_merge(
                    [$currentOrganization->getId()],
                    array_keys($this->getDbLookup()->getOrganizationsByCode('xc-hand-en-polszorg'))
                    );
        }

        if ($currentOrganization->containsCode('xc-hand-en-polszorg')) {
            return array_merge(
                    [$currentOrganization->getId()],
                    array_keys($this->getDbLookup()->getOrganizationsByCode('xpert-handtherapie'))
                    );
        }

        // Do not show data from other orgs
        return false;

        // Do show data from all other orgs
        // return true;

        // Return the organisations the user is allowed to see.
        // return array_keys($this->currentUser->getAllowedOrganizations());
    }
```

## Overruling `getOtherOrgs()`

The CP implementation shows a more complex implementation. The CP Register has a separate table `gems__consent_org2org`
where we record **per patient** wether it is accessible for another organizations. This table is maintained in the 
`RespondentConsentFormSnippet` (and elsewhere it is defined which organizations can share patients in the first place).

The recent for this complex system is that patients are tracked at a university hospital, but also at other hospitals, 
clinics and physiotherapists for treatment. 

In this case we overrode the controller function since we need information not available at the `Util.php` level, i.e. 
who is the current respondent. 

```php    
    /**
     * The organizations whose tokens are shown.
     *
     * When true: show tokens for all organizations, false: only current organization, array => those organizations
     * @return boolean|array
     */
    public function getOtherOrgs()
    {
        $respondent = $this->getRespondent();
        $otherOrgs  = $respondent->getConsentingOrgs();

        if ($otherOrgs) {
            return $otherOrgs;
        }

        return $this->util->getOtherOrgsFor($respondent->getOrganizationId());
    }

```

The actual work in this function is done in a local extension of the `Respondent` object:

```php
    /**
     * Get the consenting orgs
     *
     * @return \Gems\Util\ConsentCode
     */
    public function getConsentingOrgs()
    {
        if (null === $this->_consentingOrgs) {
            $sql = "SELECT gco2o_organization_from, gco2o_organization_from
                FROM gems__consent_org2org INNER JOIN gems__consents ON gco2o_consent = gco_description
                WHERE gco2o_id_user = ? AND gco2o_organization_to = ? AND gco_code != ?";

            $this->_consentingOrgs = $this->db->fetchPairs($sql, [
                $this->respondentId, $this->organizationId, $this->util->getConsentRejected()
            ]);

            // If array, add current org
            if (is_array($this->_consentingOrgs)) {
                $this->_consentingOrgs[$this->organizationId] = $this->organizationId;
            }
        }

        return $this->_consentingOrgs;
    }
```
