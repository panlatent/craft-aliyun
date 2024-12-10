/** global: Craft */
/** global: Garnish */

Craft.CloudUploader = Craft.BaseUploader.extend(
  {
    element: null,
    $fileInput: null,
    _totalBytes: 0,
    _uploadedBytes: 0,
    _lastUploadedBytes: 0,
    _validFileCounter: 0,
    _handleChange: null,

    init: function ($element, settings) {
      settings = $.extend({}, Craft.CloudUploader.defaults, settings);
      this.base($element, settings);
      this.element = $element[0];
      this.$dropZone = settings.dropZone;
      this._handleChange = this.handleChange.bind(this);
      this.$fileInput.on("change", this._handleChange);

      Object.entries(this.settings.events).forEach(([name, handler]) => {
        this.element.addEventListener(name, handler);
      });

      if (this.allowedKinds && !this._extensionList) {
        this._createExtensionList();
      }

      if (this.$dropZone) {
        this.$dropZone.on({
          dragover: (event) => {
            if (this.handleDragEvent(event)) {
              event.originalEvent.dataTransfer.dropEffect = "copy";
            }
          },
          drop: (event) => {
            if (this.handleDragEvent(event)) {
              this.uploadFiles(event.originalEvent.dataTransfer.files);
            }
          },
          dragenter: this.handleDragEvent,
          dragleave: this.handleDragEvent,
        });
      }
    },

    handleDragEvent: function (event) {
      if (!event?.originalEvent?.dataTransfer?.files) {
        return false;
      }

      event.preventDefault();
      event.stopPropagation();

      return true;
    },

    uploadFiles: async function (FileList) {
      const files = [...FileList];
      const validFiles = files.filter((file) => {
        let valid = true;

        if (this._extensionList?.length) {
          const matches = file.name.match(/\.([a-z0-4_]+)$/i);
          const fileExtension = matches[1];

          if (!this._extensionList.includes(fileExtension.toLowerCase())) {
            this._rejectedFiles.type.push("“" + file.name + "”");
            valid = false;
          }
        }

        if (
          this.settings.maxFileSize &&
          file.size > this.settings.maxFileSize
        ) {
          this._rejectedFiles.size.push("“" + file.name + "”");
          valid = false;
        }

        if (
          valid &&
          typeof this.settings.canAddMoreFiles === "function" &&
          !this.settings.canAddMoreFiles(this._validFileCounter)
        ) {
          this._rejectedFiles.limit.push("“" + file.name + "”");
          valid = false;
        }

        if (valid) {
          this._totalBytes += file.size;
          this._validFileCounter++;
          this._inProgressCounter++;
        }

        return valid;
      });

      this.processErrorMessages();

      if (this._validFileCounter > 0) {
        this.element.dispatchEvent(new Event("fileuploadstart"));

        for (const file of validFiles) {
          await this.uploadFile(file);
          this._inProgressCounter--;
        }
      }

      this._validFileCounter = 0;
      this._totalBytes = 0;
      this._uploadedBytes = 0;
      this._lastUploadedBytes = 0;
      this._inProgressCounter = 0;
    },

    uploadFile: async function (file) {
      const formData = Object.assign({}, this.formData, {
        filename: file.name,
        filetype: file.type,
      });
      console.log(file);

      try {
        let response = await Craft.sendActionRequest(
          "POST",
          "aliyun/assets/get-upload-url",
          {
            data: formData,
          }
        );

        await axios.put(response.data.url, file, {
          headers: {
            "Content-Type": file.type,
            "Access-Control-Allow-Origin": "*",
          },
          onUploadProgress: (axiosProgressEvent) => {
            this._uploadedBytes =
              this._uploadedBytes +
              axiosProgressEvent.loaded -
              this._lastUploadedBytes;
            this._lastUploadedBytes = axiosProgressEvent.loaded;

            this.element.dispatchEvent(
              new CustomEvent("fileuploadprogressall", {
                detail: {
                  loaded: this._uploadedBytes,
                  total: this._totalBytes,
                },
              })
            );
          },
        });

        Object.assign(formData, response.data, {
          size: file.size,
          lastModified: file.lastModified,
        });

        const image = await this.getImage(file);

        if (image) {
          const { width, height } = image;
          Object.assign(formData, { width, height });
        }

        response = await axios.post(this.settings.url, formData);
        this.element.dispatchEvent(
          new CustomEvent("fileuploaddone", { detail: response.data })
        );
      } catch (error) {
        this.element.dispatchEvent(
          new CustomEvent("fileuploadfail", {
            detail: {
              message: error?.response?.data?.message,
              filename: file.name,
            },
          })
        );
      } finally {
        this._lastUploadedBytes = 0;
        this.element.dispatchEvent(new Event("fileuploadalways"));
      }
    },

    handleChange: function (event) {
      this.uploadFiles(event.target.files);
      this.$fileInput.val("");
    },

    getImage: async function (file) {
      const image = new Image();

      try {
        image.src = URL.createObjectURL(file);
        await image.decode();
        URL.revokeObjectURL(image.src);
      } catch {
        return null;
      }

      return image;
    },

    destroy: function () {
      this.$fileInput.off("change", this._handleChange);
      this.$dropZone.off("dragover drop dragenter dragleave");

      Object.entries(this.settings.events).forEach(([name, handler]) => {
        this.element.removeEventListener(name, handler);
      });
    },
  },
  {
    defaults: {
      maxFileSize: null,
      createAction: "aliyun/assets/create-asset",
      replaceAction: "aliyun/assets/replace-file",
    },
  }
);

// Register it!
Craft.registerUploaderClass(
  "panlatent\\craft\\aliyun\\fs\\OSS",
  Craft.CloudUploader
);
