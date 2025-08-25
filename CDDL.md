## CDDL is a “weak copyleft” license, roughly similar to the LGPL.

- you are allowed to combine the CDDL covered software with your software, e.g. by static linking
- your software will not fall under the CDDL
- if you modify the CDDL-covered component, your modifications must be CDDL-licensed as well
- you must also include a notice that identifies you as the contributor for this modification
- when you distribute CDDL-covered software (whether standalone or linked with a larger software) you have to:
  -- make the source code of the CDDL-covered software available
  -- include a copy of the license with the source code
  -- inform users how to acquire the source code for CDDL-covered components “in a reasonable manner on or through a medium customarily used for software exchange”
  
you are not required to distribute the executable form of the CDDL-covered software under the CDDL license, but may choose a different license, if:
you otherwise comply with the CDDL, and the different license does not limits or alters the recipient's rights to the source code, and you make it absolutely clear that the different license is offered by you alone, not by the initial developers or contributors of the CDDL-covered software.

To summarize all of this, having a proprietary software that includes a CDDL-covered library (no matter how it is linked) is perfectly fine. For example, you might take the following steps for compliance:

Make the library source code available in a public GitHub repository under your control. If it's already available there, consider just forking it.
in your proprietary licensing agreement, note that parts of the software may be under open source licenses that provide additional permissions (and where to find more info about these components
in the included documentation of your software, include a list of all included open source components with their names, websites, copyright notices, and licenses. For the CDDL-covered components, note that the source code is available at a linked GitHub repository.
