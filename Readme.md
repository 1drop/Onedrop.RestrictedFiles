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

```yaml
Onedrop:
  RestrictedFiles:
    collectionNames: ['Protected']
```

## Restrict download to permission

This package also provides a simple way to restrict the download of a 
file to a `privilegeTarget` that can be assigned to any role.

To grant access to the files just add this privilegeTarget to a role:
```yaml
roles:
  'Some.Package:SomeUser':
    privileges:
      -
        privilegeTarget: 'Onedrop.RestrictedFiles:Download'
        permission: GRANT
```

You can disable this feature by setting:
```yaml
roles:
  'Neos.Flow:Everybody':
    privileges:
      -
        privilegeTarget: 'Onedrop.RestrictedFiles:Download'
        permission: GRANT
```

## Handling unauthorized download attempts

This package will emit a `\Neos\Flow\Security\Exception\AccessDeniedException` if 
an unauthorized access to a download occurs. There is a signal you can 
subscribe on to change that behavior to e.g. redirect to a page.

Example:

```php
<?php 

public function redirectToReferer(PersistentResource $resource, HttpRequest $httpRequest)
{
    $referer = $httpRequest->getHeader('Referer');
    if (!empty($referer)) {
        $refererUri = new Uri($referer);
        if ($refererUri->getHost() === $httpRequest->getUri()->getHost()) {
            $refererUri->setQuery('--restricted-files[accessDenied]=true');
            header('Location: ' . $refererUri->__toString());
            exit();
        }
    }
}
```

## Download tracking

By default this package tracks every download of an authenticated account.

You can disable this feature by setting:
```yaml
Onedrop:
  RestrictedFiles:
    trackProtectedDownloadsByAccount: false
```

## How it works

It uses doctrine lifecycle hooks to copy resources between 
Flow resource collections if an asset is moved into a protected
asset collection in the neos backend (or uploaded of course).

To check the access it uses the signals emitted by the PrivateResources package.
