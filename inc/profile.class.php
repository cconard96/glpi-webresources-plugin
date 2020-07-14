<?php

class PluginWebresourcesProfile extends Profile
{

   public static $rightname = "config";

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      return self::createTabEntry(__('Web Resources', 'webresources'));
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      $profile = new self();
      $profile->showForm($item->getID());
      return true;
   }

   public function showForm($profiles_id = 0, $openform = true, $closeform = true)
   {
      if (!self::canView()) {
         return false;
      }

      echo "<div class='spaced'>";
      $profile = new Profile();
      $profile->getFromDB($profiles_id);

      $can_edit = Session::haveRight(self::$rightname, UPDATE);
      if ($openform && $can_edit) {
         echo "<form method='post' action='" . $profile::getFormURL() . "'>";
      }

      $rights = [
         [
            'itemtype' => PluginWebresourcesResource::class,
            'label' => PluginWebresourcesResource::getTypeName(Session::getPluralNumber()),
            'field' => PluginWebresourcesResource::$rightname
         ]
      ];
      $matrix_options['title'] = __('Web Resources', 'webresources');
      $profile->displayRightsChoiceMatrix($rights, $matrix_options);

      if ($can_edit && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo '</div>';
   }
}
