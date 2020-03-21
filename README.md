# Configuration

In the Cockpit yaml `config/config.yaml`:

```
auth0:
    enable: true
    domain: company.eu.auth0.com
    id: xxxxLG1Phms1LsZAnrNe3xxxxxx
    scope: openid profile email
    cache: true
    default_group: author           # optional, defaults to auth0user
    session_ttl: 10*24*60           # optional, customize php session TTL
    namespace: https://company.com  # optional, see below
```

Auth0 no longer exposes `app_metadata` or user assigned roles via tokeninfo endpoint. However, you can fix this with custom Auth0 rule:

```
function (user, context, callback) {
  const namespace = 'https://company.com/';
  if(user.app_metadata && user.app_metadata.cockpit) {
    context.idToken[namespace + 'cockpit'] = user.app_metadata.cockpit;
  }
  context.idToken[namespace + 'roles'] = context.authorization.roles || [];
  callback(null, user, context);
}
```
To make this work, you also need to add `app_metadata` and `roles` scopes to your authorization scope. You also need to add `namespace: https://company.com` to the configuration under `auth0`, so that plugin knows where to read namespaced information. This way you can assign `admin` Cockpit role by assigning role `cockpit:admin` on Auth0 or by setting up custom roles for cockpit in the `app_metadata`.

```
{ "cockpit": "yourrole" }
```

## Note on user accounts

To make document author associations to work, this plugin creates copy of the Auth0 user automatically to the accounts. This user won't have a password, and will have email prefixed with `auth0:$email`, so loggin in with these generated accounts is not possible without Auth0. Generated users will also have additional `generated` flag in the user document.

