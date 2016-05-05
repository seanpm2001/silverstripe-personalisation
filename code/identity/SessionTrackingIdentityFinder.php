<?php

class SessionTrackingIdentityFinder implements TrackingIdentityFinder
{

    public function findOrCreate()
    {
        $value = session_id();
        if (!$value) {
            return;
        }

        $ident = TrackingIdentity::get_identity($this->getType(), $value);
        if (!$ident) {
            $ident = TrackingIdentity::create_identity($this->getType(), $value);
        }
        return $ident;
    }

    public function getType()
    {
        return "session";
    }
}
