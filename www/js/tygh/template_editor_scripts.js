(function(_, $) {

    var template_editor = {
        selected_file: {},
        company_id: 0,

        init: function () {

            var self = this;

            // hide all
            $('.cm-te-messages > div').hide();
            self._showErrorMessage('te-not-selected');

            //hide edit buttons
            $('.cm-te-edit').hide();

            if($.cookie.get('te_last_edited_path' + self.company_id)) {
               var path = $.cookie.get('te_last_edited_path' + self.company_id);
                $.ceAjax('request', fn_url('template_editor.init_view?dir=' + path), {
                    cache: false,
                    callback: function(data) {
                        $('.cm-te-file-tree').append(data.files_list);
                        $('.cm-te-file-tree').find('.active > ul').hide();
                        $('.cm-te-file-tree').find('.active > .cm-te-file').click();
                    }
                });
            } else {
                $.ceAjax('request', fn_url('template_editor.browse'), {
                    cache: false,
                    callback: function (data){
                        $('.cm-te-file-tree').append(data.files_list);

                        //hide action if nothing selected.
                        $('.ce-te-actions li:not(.cm-te-onsite-editing), .cm-te-create').hide();

                    }
                });
            }

            // file tree
            $(_.doc).on('click', '.cm-te-file-tree li a', function() {
                self.fileTree(this);
            });

            // change path
            $(_.doc).on('click', '.cm-te-path a', function() {
                self.changePath(this);
            });

            // get file
            $(_.doc).on('click', '.cm-te-getfile', function() {
                if(self.selected_file.fileType !== 'D' && self.selected_file.fileName.length > 0){
                    self.getFile();
                }
            });

            // rename
            $(_.doc).on('click', '.cm-te-rename', function(){
                self.rename();
            });

            // delete file or folder
            $(_.doc).on('click', '.cm-te-delete', function(){
                if(self.selected_file.fileName.length > 0){
                    self.deleteFile();
                }
            });

            // change perms
            $(_.doc).on('click', '.cm-te-chmod', function() {
                self.setPerms();
            });

            // parse perms
            $(_.doc).on('click', '.cm-te-perms', function() {
                self.parsePerms();
            });

            // restore file
            $(_.doc).on('click', '.cm-te-restore', function() {
                self.restoreFile();
            });

            // save file
            $(_.doc).on('click', '.cm-te-save-file', function() {
                fn_save_template();
            });

            // create file
            $.ceEvent('on', 'ce.formpost_add_file_form', function(form) {
                var filename = $('#new_file').val();
                self.createFile(filename);

                return false;
            });

            // create folder
            $.ceEvent('on', 'ce.formpost_add_folder_form', function(form) {
                var folder_name = $('#new_folder').val();
                self.createFolder(folder_name);

                return false;
            });

            $(_.doc).on('click', '.cm-te-upload-file', function() {
               $("#upload_path").val(self.selected_file.fileFullPath);
            });
        },

        fileTree: function(context, reinit) {
            reinit = reinit || false;

            var self = this;

            self.selected_file.filePath = $(context).data('ca-item-path');
            self.selected_file.fileFullPath = $(context).data('ca-item-full-path');
            self.selected_file.fileType = $(context).data('ca-item-type');
            self.selected_file.fileExt = $(context).data('ca-item-ext');
            self.selected_file.filePerms = $(context).data('ca-item-perms');
            self.selected_file.fileName = $(context).data('ca-item-filename');
            self.selected_file.context = context;

            // set full path to cookie
            $.cookie.set('te_last_edited_path' + self.company_id, self.selected_file.fileFullPath);

            var li = $(context).parent('li');

            //show edit buttons
            $('.cm-te-edit').show();
            $('.ce-te-actions li').show();

            // if folder click
            if(self.selected_file.fileType == 'D' && ($(li).hasClass('parent') == false) || reinit) {
                if (reinit) {
                    $(li).find('ul').remove();
                    $(li).removeClass('active').removeClass('parent');
                }

                $.ceAjax('request', fn_url('template_editor.browse?dir=' + self.selected_file.filePath + '/' + self.selected_file.fileName), {
                    cache: false,
                    callback: function(data) {
                        $(context).after(data.files_list);
                        $('.cm-te-file-tree li').removeClass('active');
                        $(li).addClass('parent active');
                    }
                });
            } else {
                $('.cm-te-file-tree li').removeClass('active');
                $(li).addClass('active');
                $(li).children('ul').slideToggle('fast');
            }

            // set overlay margin
            var overlayMargin = $(context).parents("ul").length * 15;
            $(context).find('.overlay').css('left', '-'+overlayMargin+'px');

            $('.cm-te-delete').removeClass('disabled').prop('disabled', false);


            $('.cm-te-messages > div').hide();
            // if file click
            if(self.selected_file.fileType == 'F') {
                $('.cm-te-getfile').removeClass('disabled').prop('disabled', false);
                $('.cm-te-save-file').removeClass('disabled').prop('disabled', false);
                $.ceAjax('request', fn_url('template_editor.edit'), {
                    data: {
                        file: self.selected_file.fileName,
                        file_path: self.selected_file.filePath
                    },
                    method: 'GET',
                    callback: function(data, params) {
                        template_editor.viewContent(data.content);
                    }
                });
            }

            if(self.selected_file.fileType == 'D') {
                $('.cm-te-create').show();
                $('.cm-te-getfile').addClass('disabled').prop('disabled', true);
                $('.cm-te-save-file').addClass('disabled').prop('disabled', true);
                self._showErrorMessage('te-empty-folder');

                var iconToggle = $(context).find('i');
                if($(iconToggle).is('.exicon-expand')){
                    $(iconToggle).removeClass('exicon-expand').addClass('exicon-collapse');
                } else {
                    $(iconToggle).removeClass('exicon-collapse').addClass('exicon-expand');
                }
            } else {
                $('.cm-te-create').hide();
            }

            // rebuild file path
            self.parsePath();

        },

        // load content
        viewContent: function(content) {

            var self = this;

            if(content === undefined) {
                return;
            }

            var textFormats = ['html', 'htm', 'php', 'txt', 'js', 'sql', 'ini', 'xml', 'tpl', 'css', 'less', 'json', 'yaml'];
            var imageFormats = ['jpg', 'png', 'gif', 'jpeg'];

            $('.cm-te-content > div').hide();

            if($.inArray(self.selected_file.fileExt, textFormats) !== -1) {
                $('.cm-te-content #template_text').show();
                $('#template_text').ceCodeEditor('set_value', content);
            } else if($.inArray(self.selected_file.fileExt, imageFormats) !== -1) {
                $('.cm-te-content #template_image').show();
                var imgTag = '<img src="' + self.selected_file.filePath.slice(1) + '/' + self.selected_file.fileName  + '" />';
                $('#template_image').html(imgTag);
                $('.cm-te-save-file').addClass('disabled').prop('disabled', true);
            } else {
                self._showErrorMessage('te-unknown-file');
            }

        },

        // parse path
        parsePath: function() {
            var self = this;
            var fullPath = self.selected_file.fileFullPath;
            fullPath=fullPath.split('/');

            var rm_path = fullPath.splice(0, 3); // remove from path design/themes ...

            var sub_path = [];
            var result = [];

            for(var i=0; i < fullPath.length; i++) {
                sub_path.push(fullPath[i]);
                result[i] = '<a data-ce-path="'+ rm_path.join('/') + '/' + sub_path.join('/') +'">' + fullPath[i] + '</a>';
            }
            $('.cm-te-path').html(result.join(' / '));
        },

        // change path
        changePath: function(context) {
            var path = $(context).data('ce-path');
            $('.cm-te-file-tree li a[data-ca-item-full-path="' + path + '"]').click();
        },

        // get file
        getFile: function() {
            var self = this;
            $.redirect(fn_url('template_editor.get_file?file=' + self.selected_file.fileName + '&file_path=' + self.selected_file.filePath));
        },

        rename: function() {
            var self = this;
            if (self.selected_file.fileName.length > 0) {
                var rename_to = prompt(_.tr('text_enter_filename'), self.selected_file.fileName);
                if (rename_to) {
                    $.ceAjax('request', fn_url('template_editor.rename_file?file=' + self.selected_file.fileName + '&file_path=' + self.selected_file.filePath + '&rename_to=' + rename_to), {cache: false, callback: function(data) {
                        if(data.action_type !== 'error') {

                            // change full path, name and set it to cookie
                            self.selected_file.fileName = rename_to;
                            var full_path = self.selected_file.fileFullPath.split('/');
                            full_path.splice(-1,1, rename_to);
                            self.selected_file.fileFullPath = full_path.join('/');
                            $.cookie.set('te_last_edited_path' + self.company_id, self.selected_file.fileFullPath);

                            $(self.selected_file.context).find('.item span').html(rename_to);

                            var ext = rename_to.split('.');
                            ext = ext[ext.length - 1];

                            $(self.selected_file.context).data('caItemFullPath', self.selected_file.fileFullPath);
                            $(self.selected_file.context).data('caItemExt', ext);
                            $(self.selected_file.context).data('caItemFilename', rename_to);
                            $(self.selected_file.context).click();
                        }
                    }});
                }
            }
        },

        // Delete file or directory
        deleteFile: function() {
            var self = this;
            if (self.selected_file.fileName.length > 0) {
                if (confirm(_.tr('text_are_you_sure_to_delete_file'))) {
                    $.ceAjax('request', fn_url('template_editor.delete_file'), {
                        data: {
                            file: self.selected_file.fileName,
                            file_path: self.selected_file.filePath
                        },
                        cache: false,
                        callback: function(data) {
                        if(data.action_type !== 'error') {
                            $(self.selected_file.context).parent('li').remove();
                            $('*[data-ca-item-full-path="' + self.selected_file.filePath + '"]').click();
                        }
                        }});
                }
            }
        },

        // set perms
        setPerms: function() {
            var self = this;

            var text_perms = '';
            var perms = 0;
            perms = $('#o_read').prop('checked') ? perms + 400 : perms;
            perms = $('#o_write').prop('checked') ? perms + 200 : perms;
            perms = $('#o_exec').prop('checked') ? perms + 100 : perms;
            perms = $('#g_read').prop('checked') ? perms + 40 : perms;
            perms = $('#g_write').prop('checked') ? perms + 20 : perms;
            perms = $('#g_exec').prop('checked') ? perms + 10 : perms;
            perms = $('#w_read').prop('checked') ? perms + 4 : perms;
            perms = $('#w_write').prop('checked') ? perms + 2 : perms;
            perms = $('#w_exec').prop('checked') ? perms + 1 : perms;

            text_perms = $('#o_read').prop('checked') ? text_perms + 'r' : text_perms + '-';
            text_perms = $('#o_write').prop('checked') ? text_perms + 'w' : text_perms + '-';
            text_perms = $('#o_exec').prop('checked') ? text_perms + 'x' : text_perms + '-';
            text_perms = $('#g_read').prop('checked') ? text_perms + 'r' : text_perms + '-';
            text_perms = $('#g_write').prop('checked') ? text_perms + 'w' : text_perms + '-';
            text_perms = $('#g_exec').prop('checked') ? text_perms + 'x' : text_perms + '-';
            text_perms = $('#w_read').prop('checked') ? text_perms + 'r' : text_perms + '-';
            text_perms = $('#w_write').prop('checked') ? text_perms + 'w' : text_perms + '-';
            text_perms = $('#w_exec').prop('checked') ? text_perms + 'x' : text_perms + '-';

            var recursive =  $('#chmod_recursive').prop('checked');

            if (self.selected_file.fileName.length > 0) {
                $.ceAjax('request', fn_url('template_editor.chmod'), {
                    data: {
                        file: self.selected_file.fileName,
                        file_path: self.selected_file.filePath,
                        perms: perms,
                        r: recursive
                    },
                    cache: false,
                    callback: function(data) {
                        if (data.action_type !== 'error') {
                            $(self.selected_file.context).data('ca-item-perms', text_perms);
                            self.selected_file.filePerms = text_perms;
                        }
                    }
                });
            }
        },

        parsePerms: function() {
            var self = this;

            var perms = self.selected_file.filePerms;

            $('#o_read').prop('checked', (perms.charAt(0) == '-') ? false : true);
            $('#o_write').prop('checked', (perms.charAt(1) == '-') ? false : true);
            $('#o_exec').prop('checked', (perms.charAt(2) == '-') ? false : true);
            $('#g_read').prop('checked', (perms.charAt(3) == '-') ? false : true);
            $('#g_write').prop('checked', (perms.charAt(4) == '-') ? false : true);
            $('#g_exec').prop('checked', (perms.charAt(5) == '-') ? false : true);
            $('#w_read').prop('checked', (perms.charAt(6) ==    '-') ? false : true);
            $('#w_write').prop('checked', (perms.charAt(7) == '-') ? false : true);
            $('#w_exec').prop('checked', (perms.charAt(8) == '-') ? false : true);
        },

        // Restore file from the repository
        restoreFile: function() {
            var self = this;
            if (confirm(_.tr('text_restore_question'))) {
                $.ceAjax('request', fn_url('template_editor.restore'), {
                    data: {
                        file: self.selected_file.fileName,
                        file_path: self.selected_file.filePath
                    },
                    cache: false,
                    callback: function(response_data, params, response_text) {
                        if (typeof(response_data.content) != 'undefined') {
                            self.viewContent(response_data.content);
                            
                            var li = $(self.selected_file.context).parent('li');
                            self.fileTree(li.find('a:first'), true);

                            $(li).find('.exicon-expand').removeClass('exicon-expand').addClass('exicon-collapse');
                        }
                    }
                });
            }

            return false;
        },

        // Create file or directory
        createFile: function(filename)
        {
            var self = this;
            var file = filename;
            var file_path = this.selected_file.fileFullPath;
            var ext = '';

            if (file.indexOf('.')) {
                ext = file.split('.').pop();
            }

            $.ceAjax('request', fn_url('template_editor.create_file'), {
                data: {
                    file: file,
                    file_path: file_path
                },
                callback: function(response_data, params, response_text) {
                    // Process response if no errors
                    if (typeof(response_data.action_type) != 'undefined' && response_data.action_type == '') {
                        $(self.selected_file.context).closest('.active').find('ul:first').append('<li><a data-ca-item-full-path="' + file_path + '/' + file + '" data-ca-item-path="' + file_path + '" data-ca-item-filename="' + file + '" data-ca-item-ext="' + ext + '" data-ca-item-type="F" data-ca-item-perms="rw-rw-rw-" class="cm-te-file" id="file_id_' + (new Date()).getTime() + '"><span class="overlay"></span><span class="item"><i class="icon-file"></i><span>' + file + '</span></span></a><li>');
                    }
                },
                cache: false
            });
            return true;
        },

        // Create file or directory
        createFolder: function(folder)
        {
            var self = this;
            var folder_path = this.selected_file.fileFullPath;

            $.ceAjax('request', fn_url('template_editor.create_folder'), {
                data: {
                    folder: folder,
                    folder_path: folder_path
                },
                callback: function(response_data, params, response_text) {
                    // Process response if no errors
                    if (typeof(response_data.action_type) != 'undefined' && response_data.action_type == '') {
                        $(self.selected_file.context).closest('.active').find('ul:first').append('<li><a data-ca-item-full-path="' + folder_path + '/' + folder + '" data-ca-item-path="' + folder_path + '" data-ca-item-filename="' + folder + '" data-ca-item-type="D" data-ca-item-perms="rwxr-xr-x" class="cm-te-file" id="file_id_' + (new Date()).getTime() + '"><span class="overlay"></span><span class="item"><i class="exicon-expand"></i><span>' + folder + '</span></span></a><li>');
                    }
                },
                cache: false
            });
            return true;
        },

        _showErrorMessage: function(type) {
            $('.cm-te-content > div').hide();
            $('.cm-te-messages > div').hide();
            $('.cm-te-messages .' + type).show();
        }

    };

    _.template_editor = template_editor;

    $(document).ready(function() {
        template_editor.init();
    });

}(Tygh, Tygh.$));