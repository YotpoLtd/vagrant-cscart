# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  # Every Vagrant virtual environment requires a box to build off of.
  config.vm.box = "precise64"

  # The url from where the 'config.vm.box' box will be fetched if it
  # doesn't already exist on the user's system.
  config.vm.box_url = "http://cloud-images.ubuntu.com/vagrant/precise/current/precise-server-cloudimg-amd64-vagrant-disk1.box"
  # config.vm.box_url = "http://files.vagrantup.com/precise64_vmware.box"


  # Create a forwarded port mapping which allows access to a specific port
  # within the machine from a port on the host machine. In the example below,
  # accessing "localhost:8080" will access port 80 on the guest machine.
  config.vm.network :forwarded_port, guest: 80, host: 8080, autocorrect: true

  # Create a private network, which allows host-only access to the machine
  # using a specific IP.

  # Create a public network, which generally matched to bridged network.
  # Bridged networks make the machine appear as another physical device on
  # your network.
  # config.vm.network :public_network

  # If true, then any SSH connections made will enable agent forwarding.
  # Default value: false
  # config.ssh.forward_agent = true

  # Share an additional folder to the guest VM. The first argument is
  # the path on the host to the actual folder. The second argument is
  # the path on the guest to mount the folder. And the optional third
  # argument is a set of non-required options.
  config.vm.synced_folder "./www", "/var/www",
    owner: 'www-data',
    group: 'www-data'

  # Provider-specific configuration so you can fine-tune various
  # backing providers for Vagrant. These expose provider-specific options.
  #
  
  # Performance mods can be bad for certain configurations may need to comment out
  config.vm.provider "virtualbox" do |vb|
    # Virtualbox Name
    vb.customize ["modifyvm", :id, "--name", "Cs-Cart-VM", "--ostype", "Ubuntu_64"]
    # Memory
    vb.customize ["modifyvm", :id, "--memory", "4092"]
	#CPU up to 4 cores and ioapic
	vb.customize ["modifyvm", :id, "--ioapic", "on"]
	vb.customize ["modifyvm", :id, "--cpus", "4"]
	vb.customize ["modifyvm", :id, "--pae", "on"]
    # Chipset (Supposedly better CPU performance)
    vb.customize [ "modifyvm", :id, "--chipset", "ich9" ]
    # NIC 1 (Better TCP over NAT performance, at least on Windows)
	vb.customize ["modifyvm", :id, "--nic1", "nat", "--nictype1", "virtio"] 
	vb.customize ["modifyvm", :id, "--natsettings1", "9000,1024,1024,1024,1024"]  
	# SSD Settings
    vb.customize ["storagectl", :id, "--name", "SATA Controller", "--controller", "IntelAHCI", "--portcount", "1", "--hostiocache", "on"]
    vb.customize ["storageattach", :id, "--storagectl", "SATA Controller", "--port", "0", "--device", "0", "--nonrotational", "on"]
  end

  # config.vm.provider :vmware_fusion do |vmware|
  #   vmware.vmx["memsize"] = "1024"
  #   vmware.vms["numvcpus"] = "1"
  # end

  # install ansible in the vm, then execute it to prevent the need to have
  #  it installed on the host server
  config.vm.provision :shell, :path => "provisioning/setup_ansible.sh"
  config.vm.provision :shell, :path => "ansible_provisioning.sh"

  # config.vm.provision "ansible" do |ansible|
  #   ansible.playbook = "provisioning/lamp.yml"
  #   ansible.verbose = "extra"
  # end

end
