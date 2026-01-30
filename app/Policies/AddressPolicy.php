<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\SiteUser;

class AddressPolicy
{
    public function viewAny()
    {
        return true;
    }

    public function create()
    {
        return true;
    }

    public function view(SiteUser $siteUser, Address $address)
    {
        return $siteUser->id === $address->site_user_id;
    }

    public function update(SiteUser $siteUser, Address $address)
    {
        return $siteUser->id === $address->site_user_id;
    }

    public function delete(SiteUser $siteUser, Address $address)
    {
        return $siteUser->id === $address->site_user_id;
    }
}
