# Replace all get_option('image_optimizer_settings') calls
s/\$settings = get_option('image_optimizer_settings', \[\]);/\/\/ Settings injected via constructor\n        $config = $this->config;/g

# Then replace $settings['auto_optimize'] with $config->should_auto_optimize()
s/\$settings\['auto_optimize'\]/$config->should_auto_optimize()/g

# Replace $settings['jpeg_quality'] 
s/\$settings\['jpeg_quality'\]/$config->get_jpeg_quality()/g

# Replace $settings['png_compression']
s/\$settings\['png_compression'\]/$config->get_png_compression()/g

# Replace $settings['create_webp']
s/\$settings\['create_webp'\]/$config->should_create_webp()/g

# Replace $settings['webp_quality']
s/\$settings\['webp_quality'\]/$config->get_webp_quality()/g

# Replace $settings['strip_metadata']
s/\$settings\['strip_metadata'\]/$config->should_strip_metadata()/g

# Replace $settings['progressive_encoding']
s/\$settings\['progressive_encoding'\]/$config->should_use_progressive_encoding()/g
