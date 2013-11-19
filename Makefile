TARGET := shoppilot-prestashop
all:
	rm -f $(TARGET).zip
	zip -r $(TARGET).zip shoppilot

clean:
	rm -f $(TARGET).zip
