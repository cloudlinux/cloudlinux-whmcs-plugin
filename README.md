CloudLinux plugin for WHMCS
============================

CloudLinux Licenses For WHMCS allows you to automatically provision CloudLinux, Imunify360, and KernelCare licenses along with selected products. You can provision them for free or as a paid add-on to your product. Owing to CloudLinux Licenses add-on, all module commands on your main product are automatically reproduced on the license product.

------

# Deploy WHMCS plugin

To build zip archive with plugin run `sh build-whmcs-cl-plugin.sh`, then copy archive to `<WHMCS_ROOT>` and make unzip.

Further information can be found at "WHMCS plugin" on https://docs.imunify360.com/whmcs_plugin.

--------
# Run e2e tests
* Make a copy of `CloudLinux-plugin/e2e-tests/.whmcs.sample` and fill all environment variables
* Install required npm packages `npm i`
* Run tests `npm run protractor`

--------

# Licensing
Plugin code itself is licensed under the GPL License, Version 2.0 (see
[LICENSE](https://github.com/cloudlinux/cloudlinux-whmcs-plugin/blob/master/LICENSE)). 
