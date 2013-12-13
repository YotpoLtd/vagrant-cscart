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
  config.vm.provider :virtualbox do |virtualbox|
    ## Don't boot with headless mode
    #vb.gui = true
    virtualbox.customize ["modifyvm", :id, "--name", "ansible-ubuntu-php"]
    virtualbox.customize ["modifyvm", :id, "--memory", "512"]
    # use host OS for dns lookup
    virtualbox.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
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
