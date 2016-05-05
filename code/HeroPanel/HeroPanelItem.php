<?php

class HeroPanelItem extends DataObject
{

    public static $db = array(
        "Name" => "Varchar(255)",
        "Link" => "Varchar(255)",
        "ImageType" => "Int",
        "LinkType" => "Int",
        "Sequence" => "Int"
    );

    public static $has_one = array(
        "Page" => "Page",
        "PersonalisationScheme" => "PersonalisationScheme",
        "HeroImage" => "Image",
        "InternalLink" => "SiteTree"
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $internalExternal = array(1 => "Internal", 2 => "External");
        $optionSet = new DropDownField("LinkType", "Add internal or external link", $internalExternal);
        $fields->addFieldToTab("Root.Main", $optionSet, "Link");
        $fields->addFieldToTab("Root.Main", new TreeDropdownField("InternalLinkID", "Internal Link", "SiteTree", "ID", "Title"), "Link");
        $options = array( 1 => "HeroImage", 2 => "Personalisation Scheme");
        $imageOrScheme = new DropDownField("ImageType", "Image Or Personalisation Scheme", $options);
        $fields->addFieldToTab("Root.Main", $imageOrScheme, "PersonalisationSchemeID");
        $fields->removeByName("HeroPanelID");
        $fields->removeByName("PageID");
        return $fields;
    }

    public function getHeroLink()
    {
        if ($this->InternalLinkType == 1 && $page = Page::get_by_id("Page", (int)$this->InternalLinkID)) {
            return $page->Link();
        } elseif ($this->InternalLinkType == 2) {
            return $this->Link;
        } else {
            return null;
        }
    }

    public function getHeroObject()
    {
        if ($this->ImageType == 1) {
            return $this->HeroImage();
        } elseif ($this->ImageType == 2) {
            return $this->PersonalisedHero();
        } else {
            return null;
        }
    }

    public function PersonalisedHero()
    {
        if ($this->PersonalisationSchemeID && $ps = PersonalisationScheme::get_by_id("PersonalisationScheme",  $this->PersonalisationSchemeID)) {
            return PersonalisationScheme::personalise_with($ps->Title, Controller::curr());
        } else {
            return null;
        }
    }
}
