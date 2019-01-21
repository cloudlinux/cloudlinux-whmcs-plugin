### WHMCS setup
1. Add user with all fields filled as `e2e` and email `e2e@email.com`
(https://whmcs.cloudlinux.com/admin/clientsadd.php)
2. Add product group called `e2e`
(https://whmcs.cloudlinux.com/admin/configproducts.php?action=creategroup)
### Tests running
Minimal parameters (for common whmcs server):
```
USE_STANDALONE_SELENIUM=1 protractor
```
All parameters available in snippets or `./.whmcs.sample` file