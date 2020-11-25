<?
/* $Id: message.inc.php 705 2006-12-11 09:53:06Z kx $ */

function DeleteMessage ($MessageID, $ClassID)
{
  global $db;

  $delete = "delete from Message".$ClassID;
  $delete .= " where Message_ID='".$MessageID."'";
  $Result = $db->query ($delete);

  $DeleteMessageParent = "delete from Message".$ClassID." where Parent_Message_ID='".$MessageID."'";
  $db->query ($DeleteMessageParent);

  if ($Result) return 1;
  return 0;
}
?>
