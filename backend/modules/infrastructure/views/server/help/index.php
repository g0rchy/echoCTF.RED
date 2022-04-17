List of infrastructure servers that are capable of launching and running docker and VM targets.

The fields include:
* **ID**: A unique numeric identifier for this server (automatically generated by the system)
* **Name**: A distinguishable name for the server
* **IP**: The IP address of the server
* **Network**: The docker network name that the target will be connected to **OR** the network interface that the VM will be binded to.
* **Description**: A short description for the server
* **Service**: Type of service (currently only `Docker` is available)
* **Connstr**: The connection string used to access this service (eg `tcp://1.2.3.4:2376`)
* **Provider ID**: An id or link to help you identify the system on your cloud provider (only visible on view and update operations)

**NOTE**: These servers are get selected at random from the frontend when the instance is getting started.