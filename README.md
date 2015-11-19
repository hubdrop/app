HubDrop
=======

HubDrop.org is a web service that uses this app to mirror git repositories.

This repo is a symfony web app with CLI tools for creating and managing git mirrors. 


Commands
========

HubDrop.org works using a CLI tool contained in this repo.  This is a symfony app, so the `app/console` file is used.

```
$ app/console

hubdrop
  hubdrop:configure                     Configure hubdrop authorizations.
  hubdrop:mirror                        Create a new mirror of a Drupal.org project.
  hubdrop:mirror:all                    Create all mirrors based on the GitHub organization's repos.
  hubdrop:source                        Set the source of a hubdrop mirror. Must be github or drupal.
  hubdrop:status                        Check the status of a certain project.
  hubdrop:update                        Update a mirror of HubDrop.
  hubdrop:update:all                    Update all mirrors of HubDrop.
  hubdrop:update_maintainers            Update GitHub maintainers based on Drupal.org info.
  
```
