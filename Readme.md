# Onedrop.RestrictedFiles

## What it does

This package allows your Neos editors to make resources protected
via the Neos MediaBrowser.

You can define a list of collection titles that should be protected
and this package will handle the resource manipulation internally to 
ensure that the files are then protected.

This package depends on `wwwision/privateresources` and you can 
check the configuration of that package on how to configure the protected
resources themselves.

## Configuration

You should create an AssetCollection in the Neos backend
and configure the title of that collection to be protected:

    Onedrop:
      RestrictedFiles:
        collectionNames: ['Protected']


It uses doctrine lifecycle hooks to copy resources between 
Flow resource collections if an asset is moved into a protected
asset collection in the neos backend (or uploaded of course).
